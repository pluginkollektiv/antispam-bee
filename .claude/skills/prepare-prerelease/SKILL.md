---
name: prepare-prerelease
description: >-
  Prepare a prerelease of Antispam Bee (3.0.0-beta.N, 3.0.0-rc.N) or a stable
  release — version bump, the `prepare-*` branch that selects the detection
  comparison's baseline, tagging, and verifying the verdict snapshot got
  attached to the release. Use when asked to prepare, cut, or release a version.
---

# Preparing a release

This walks the full path from a version bump to a published release, including
what the spam-detection comparison does along the way and which of its failures
are expected.

Ask for the target version if it was not given (e.g. `3.0.0-beta.2`). Everything
below refers to it as `<version>`. Work from `v3`, never `master` — `master`
carries the 2.x line.

## 1. Branch first — the name selects the comparison baseline

Create the branch **before** committing, because its name is the only thing that
tells the comparison what to compare against:

```bash
git switch v3 && git pull
git switch -c prepare-<version>       # or chore/prepare-<version>
```

`scripts/resolve-baseline.php` in
[`2ndkauboy/asb-detection-compare`](https://github.com/2ndkauboy/asb-detection-compare)
strips any path prefix, requires a `prepare-` segment, and then picks:

- for a **prerelease**, the highest prerelease tag of the same `X.Y.Z` core below
  it (`prepare-3.0.0-rc.1` → `3.0.0-beta.1`), falling back to the highest stable
  tag when this is the first prerelease of that core;
- for a **stable** version, the highest stable tag below it (`prepare-3.0.0` →
  `2.11.12`) — note this deliberately skips the betas.

Any other branch name means no comparison runs at all. If you have the harness
checked out, confirm the resolution before pushing rather than assuming it:

```bash
git tag > /tmp/asb-tags.txt
php <harness>/scripts/resolve-baseline.php prepare-<version> /tmp/asb-tags.txt
```

## 2. Bump the version in three places

Model on `1f6576a` ("chore: update plugin version to 3.0.0-beta.1 and adjust
readme entries", #760), which touched exactly two files:

- `antispam_bee.php` — **both** the `* Version:` plugin header and the
  `define( __NAMESPACE__ . '\PLUGIN_VERSION', … )` constant.
- `readme.txt` — the `* Stable tag:` header, plus the `## Changelog ##` entry
  for this release.

Verify all three landed, and that nothing still names the previous version:

```bash
grep -nE "Version:|PLUGIN_VERSION" antispam_bee.php
grep -n  "Stable tag:" readme.txt
```

Then run the standard checks from `AGENTS.md` (`composer cs`, `composer phpstan`,
`composer test:unit`) and open the PR against `v3`.

## 3. Pushing the branch starts a full-corpus comparison

`.github/workflows/spam-detection-comparison.yml` runs on `push` to `prepare-*`
and `chore/prepare-*`, and selects the **full** corpus for push events (pull
requests get the small one). Expect roughly half an hour.

**It is meant to fail the flip gate.** `fail-on-flips` is `true` for anything
that is not an opt-out manual run, and a release intentionally changes
behaviour, so flips are the *product* of this run, not a defect. Read the flip
table and confirm every flip is a change you meant to ship. Each flip is
labelled with how the comment was classified historically (`[was: manually]`,
`[was: unflagged]`).

The run normally needs a **single pass**: if the baseline release already carries
a verdict snapshot, classifying the baseline is skipped. When it re-classifies
instead, the verify step logs why — the usual cause is an option-fixture
mismatch, since a `lang_api` run uses a different fixture and is fingerprinted
separately. That costs time but is not a correctness problem.

Snapshots are keyed by corpus (`asb-snapshot-full-<token>.tsv.gz`), so a full-
corpus run only ever matches a full-corpus baseline.

## 4. Tag and publish the release

**A prerelease tag never deploys to WordPress.org.**
`.github/workflows/wordpress-plugin-deploy.yml` triggers on
`push: tags: ["*", "!*-*"]` — the negation excludes every tag containing a
hyphen, so `3.0.0-beta.2` is skipped and only a hyphen-free stable tag deploys.
Rely on this as the safety guard, and re-read that glob before tagging a
**stable** version, where the deploy is real and immediate.

After merging, tag the merge commit and publish a GitHub **release** for it,
ticking *Set as a pre-release* for any hyphenated version. Publishing the
release — not pushing the tag — is what drives the next step.

## 5. Verify the snapshot was attached

`.github/workflows/attach-snapshot.yml` fires on `release: published` and copies
this run's verdict snapshot onto the release, where the *next* prepare run finds
it and skips classifying the baseline. Confirm the asset actually appeared:

```bash
gh release view <version> --json assets --jq '.assets[].name'
```

Expect `asb-snapshot-full-<token>.tsv.gz`.

Two non-problems:

- The `small` matrix leg logs "No small-corpus snapshot in that run; nothing to
  attach." A version prepared with the full corpus has no small-corpus artifact;
  the workflow reports and skips it by design.
- If the full leg fails, nothing downstream breaks — the next comparison simply
  classifies its baseline as it always did, only slower. Fix it before the
  following release rather than re-cutting this one.
