# deepsec

This directory holds the [deepsec](https://www.npmjs.com/package/deepsec)
config for the parent repo. Checked into git so teammates inherit
project context (auth shape, threat model, custom matchers); generated
scan output is gitignored.

Currently configured project: `antispam-bee` (target: `..`).

## Setup

`npx deepsec init` created this workspace and normally completes its
install, exact Vercel project link, Sandbox/model probes, threat model,
coverage-guided scans, custom matchers, and first AI processing run.

If setup was interrupted, run `pnpm deepsec setup` here or re-run the
original init command. Checkpoints in `data/antispam-bee/setup/setup-state.json`
skip completed work. The linked Vercel project is always in Sandbox scope.

Use `--model-auth direct --ai-provider <provider>
--ai-api-key-env <ENV_NAME>` to use a user-owned model credential; secret
values remain in the environment or `.env.local`. Use `--model-auth local`
to rely on a machine-wide `claude`/`codex` login instead — no API key or
env vars needed.

## Daily commands

```bash
pnpm deepsec scan
pnpm deepsec process     --concurrency 5
pnpm deepsec revalidate  --concurrency 5                  # cuts FP rate
pnpm deepsec export      --format md-dir --out ./findings
```

`--project-id` is auto-resolved while there's only one project in
`deepsec.config.ts`. Once you've added a second project, pass
`--project-id antispam-bee` (or whichever id you want) explicitly.

`scan` is free (regex only). `process` is the AI stage (≈$0.30/file
on Opus by default). Run state goes to `data/antispam-bee/`.

## Long sweeps under a subscription session limit

With `--model-auth local` the AI stage bills against the machine's `claude`
subscription, and that has a session limit. Parallel batches exhaust it before
any of them finish, and a batch killed mid-investigation banks nothing — a
`--concurrency 12` sweep of 71 files produced 0 analyses and 17 failed batches.

Use the driver instead. It runs one file per agent invocation, records each
completed file, and stops cleanly the moment the limit is hit:

```bash
.deepsec/bin/sweep-one-at-a-time.sh            # until the limit stops it
.deepsec/bin/sweep-one-at-a-time.sh --max 5    # cap the files per run
.deepsec/bin/sweep-one-at-a-time.sh --list     # what is left
```

Exit code 2 means the session limit stopped it; re-run after the reset and it
resumes with the files it has not reached yet. State and per-file logs live in
`data/antispam-bee/debug/sweep/` (gitignored, absolute paths, machine-local).

## Adding another project

To scan another codebase from this same `.deepsec/`:

```bash
pnpm deepsec init-project ../some-other-package   # path relative to .deepsec/
```

Appends an entry to `deepsec.config.ts` and writes
`data/<id>/{INFO.md,SETUP.md,project.json}`. Open the new SETUP.md
in your agent to fill in INFO.md.

## Layout

```
deepsec.config.ts        Project list (one entry per scanned repo)
data/antispam-bee/
  INFO.md                Repo context — checked into git, hand-curated
  SETUP.md               Agent setup prompt — checked in, deletable
  project.json           Generated (gitignored)
  files/                 One JSON per scanned source file (gitignored)
  runs/                  Run metadata (gitignored)
  reports/               Generated markdown reports (gitignored)
AGENTS.md                Pointer for coding agents
.env.local               Tokens (gitignored)
```

## Docs

After `pnpm install`:

- Skill: `node_modules/deepsec/SKILL.md`
- Full docs: `node_modules/deepsec/dist/docs/{getting-started,configuration,models,writing-matchers,plugins,architecture,data-layout,vercel-setup,faq}.md`

Or browse on
[GitHub](https://github.com/vercel/deepsec/tree/main/docs).
