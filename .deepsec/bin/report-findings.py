#!/usr/bin/env python3
"""Collect the sweep's findings into a single readable Markdown document.

The per-file records under `.deepsec/data/` are gitignored JSON, so the results
of a sweep otherwise exist only as machine-readable state on one machine.

A record accumulates findings across analyses: re-analysing a file adds to it
rather than replacing what is there. A finding therefore outlives the code it
describes, and the record's `fileHash` looks current afterwards, which hides
the staleness. Only findings produced by the file's most recent analysis
(the last `analysisHistory` entry) are counted; earlier ones are listed
separately as superseded.

Only records that are both listed in `done.txt` and carry `status: analyzed`
are counted. A file recorded as done whose record says `status: error` did not
run to completion: its findings are partial and, more importantly, the absence
of findings means nothing. Those are listed separately rather than counted, so
the totals cannot quietly include a file that was never really analysed.

Usage: .deepsec/bin/report-findings.py > .deepsec/FINDINGS.md
"""
import datetime
import glob
import json
import os

BASE = os.path.join(os.path.dirname(__file__), "..", "data", "antispam-bee")
ORDER = {"HIGH_BUG": 0, "MEDIUM": 1, "BUG": 2}
LABEL = {"HIGH_BUG": "High", "MEDIUM": "Medium", "BUG": "Bug"}


def count_targets():
    """Count the files the sweep targets: the plugin's own PHP.

    Derived from the tree rather than hardcoded, because the set grows with the
    source — a new file would otherwise be silently missing from the totals.
    """
    root = os.path.join(os.path.dirname(__file__), "..", "..")
    total = 1 if os.path.exists(os.path.join(root, "antispam_bee.php")) else 0
    for dirpath, _dirnames, filenames in os.walk(os.path.join(root, "src")):
        total += sum(1 for name in filenames if name.endswith(".php"))

    return total



def split_by_analysis(record):
    """Split a record's findings into the current ones and the superseded ones.

    `producedByRunId` is compared against the run id of the newest
    `analysisHistory` entry. `lastScannedRunId` is deliberately not used: it
    comes from the scan phase and never matches an analysis run id.
    """
    history = record.get("analysisHistory") or []
    if not history:
        return list(record.get("findings", [])), []

    latest = max(history, key=lambda h: h.get("investigatedAt") or "")
    latest_run = latest.get("runId")
    if not latest_run:
        return list(record.get("findings", [])), []

    current, superseded = [], []
    for finding in record.get("findings", []):
        run = finding.get("producedByRunId")
        # A finding with no run id predates the field; treat it as current.
        (current if run in (None, latest_run) else superseded).append(finding)

    return current, superseded


def anchor(path):
    return path.replace("/", "").replace(".", "").replace("_", "").lower()


def emit_finding(x):
    lines = x.get("lineNumbers") or []
    loc = f" — line{'s' if len(lines) != 1 else ''} {', '.join(map(str, lines))}" if lines else ""
    print(f"### {LABEL.get(x.get('severity'), x.get('severity'))}: {x.get('title')}\n")
    meta = [f"`{x.get('vulnSlug')}`"]
    if x.get("confidence"):
        meta.append(f"confidence: {x['confidence']}")
    print(f"*{' · '.join(meta)}{loc}*\n")
    print((x.get("description") or "").strip() + "\n")
    if x.get("recommendation"):
        print(f"**Recommendation.** {x['recommendation'].strip()}\n")


def main():
    total_targets = count_targets()
    with open(f"{BASE}/debug/sweep/done.txt") as fh:
        done = [l.strip() for l in fh if l.strip()]

    records = {}
    for path in glob.glob(f"{BASE}/files/**/*.json", recursive=True):
        with open(path) as fh:
            d = json.load(fh)
        records[d["filePath"]] = d

    ok = {f: records[f] for f in done if records.get(f, {}).get("status") == "analyzed"}
    errored = {f: records[f] for f in done if records.get(f, {}).get("status") == "error"}

    # Only what the most recent analysis of each file produced is current.
    current = {}
    superseded = {}
    for path, record in ok.items():
        now, before = split_by_analysis(record)
        current[path] = now
        if before:
            superseded[path] = before

    total = sum(len(v) for v in current.values())
    suptot = sum(len(v) for v in superseded.values())
    errtot = sum(len(d.get("findings", [])) for d in errored.values())
    clean = sorted(f for f in ok if not current[f])
    withf = sorted(
        (f for f in ok if current[f]),
        key=lambda f: (min(ORDER.get(x.get("severity"), 9) for x in current[f]), f),
    )

    sev = {}
    for findings in current.values():
        for x in findings:
            sev[x.get("severity")] = sev.get(x.get("severity"), 0) + 1

    print("# Antispam Bee — `deepsec` sweep findings\n")
    print(
        "Automated review of the plugin's own PHP by `deepsec` (Claude Opus 5, thinking level "
        f"`xhigh`), one file per agent run. Generated {datetime.date.today().isoformat()} "
        "against `v3`.\n"
    )
    print(
        f"**{total} findings across {len(withf)} of {len(ok)} fully analysed files.** "
        f"{len(clean)} analysed files came back clean. "
        f"{total_targets - len(done)} of the {total_targets} target files have not been swept yet."
        + (
            f" A further {suptot} finding{'s' if suptot != 1 else ''} from earlier analyses "
            f"{'have' if suptot != 1 else 'has'} been superseded and {'are' if suptot != 1 else 'is'} "
            "listed at the end.\n"
            if suptot
            else "\n"
        )
    )
    print("| Severity | Count |")
    print("| --- | --- |")
    for s in sorted(sev, key=lambda s: ORDER.get(s, 9)):
        print(f"| {LABEL.get(s, s)} | {sev[s]} |")
    print()
    print(
        "Severity is as reported by the tool. Nothing here has been triaged or confirmed by "
        "hand — treat each entry as a lead to verify, not a defect of record.\n"
    )

    print("## Contents\n")
    for f in withf:
        n = len(current[f])
        print(f"- [`{f}`](#{anchor(f)}) — {n} finding{'s' if n != 1 else ''}")
    if errored:
        print(
            "- [Unreliable results](#unreliable-results) — "
            f"{len(errored)} file{'s' if len(errored) != 1 else ''} recorded as done despite erroring"
        )
    if superseded:
        print(
            "- [Superseded findings](#superseded-findings) — "
            f"{suptot} from earlier analyses of {len(superseded)} "
            f"file{'s' if len(superseded) != 1 else ''}"
        )
    print()

    for f in withf:
        print(f"## `{f}`\n")
        for x in sorted(current[f], key=lambda x: ORDER.get(x.get("severity"), 9)):
            emit_finding(x)

    if errored:
        print("## Unreliable results\n")
        print(
            f"{len(errored)} file{'s were' if len(errored) != 1 else ' was'} marked done by an "
            "early version of the sweep script that recorded progress without checking whether "
            "the analysis actually completed (fixed in `b70ab85`). Their records carry "
            f"`status: error`, so the {errtot} finding{'s' if errtot != 1 else ''} below "
            f"{'are' if errtot != 1 else 'is'} partial and the absence of further findings "
            "means nothing.\n"
        )
        print(
            "Re-analyse by removing these lines from "
            "`.deepsec/data/antispam-bee/debug/sweep/done.txt` and re-running the sweep:\n"
        )
        print("```")
        for f in sorted(errored):
            print(f)
        print("```\n")
        for f in sorted(errored):
            n = len(errored[f].get("findings", []))
            print(f"### `{f}`\n")
            print(f"`status: error` · {n} partial finding{'s' if n != 1 else ''} recorded.\n")
            for x in errored[f].get("findings", []):
                desc = " ".join((x.get("description") or "").split())[:300]
                print(
                    f"- **{LABEL.get(x.get('severity'), x.get('severity'))}: {x.get('title')}** "
                    f"— {desc}…\n"
                )

    if superseded:
        print("## Superseded findings\n")
        print(
            "These were produced by an earlier analysis of a file that has since been "
            "re-analysed. `deepsec` adds to a record rather than replacing it, so they "
            "persist even though the newer analysis did not reproduce them — usually "
            "because the code they describe was changed or fixed in the meantime.\n"
        )
        print(
            "They are kept for reference and are **not** counted in the totals above. "
            "Verify against the current source before acting on any of them.\n"
        )
        for f in sorted(superseded):
            print(f"### `{f}`\n")
            for x in sorted(superseded[f], key=lambda x: ORDER.get(x.get("severity"), 9)):
                label = LABEL.get(x.get("severity"), x.get("severity"))
                run = x.get("producedByRunId", "unknown run")
                print(f"- **{label}: {x.get('title')}** — from run `{run}`")
            print()

    if clean:
        print("## Analysed with no findings\n")
        for f in clean:
            print(f"- `{f}`")
        print()

    print("## Not yet analysed\n")
    print(
        f"{total_targets - len(done)} of the {total_targets} target files (`src/**/*.php` plus "
        "`antispam_bee.php`; vendor and tests are excluded) remain. Resume with:\n"
    )
    print("```")
    print(".deepsec/bin/sweep-one-at-a-time.sh --max N")
    print("```\n")
    print(
        "Progress is recorded in `.deepsec/data/antispam-bee/debug/sweep/done.txt`, so a re-run "
        "skips completed files. Budget roughly $1.50 per file.\n"
    )


if __name__ == "__main__":
    main()
