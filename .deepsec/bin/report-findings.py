#!/usr/bin/env python3
"""Collect the sweep's findings into a single readable Markdown document.

The per-file records under `.deepsec/data/` are gitignored JSON, so the results
of a sweep otherwise exist only as machine-readable state on one machine.

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
TOTAL_TARGETS = 72


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
    with open(f"{BASE}/debug/sweep/done.txt") as fh:
        done = [l.strip() for l in fh if l.strip()]

    records = {}
    for path in glob.glob(f"{BASE}/files/**/*.json", recursive=True):
        with open(path) as fh:
            d = json.load(fh)
        records[d["filePath"]] = d

    ok = {f: records[f] for f in done if records.get(f, {}).get("status") == "analyzed"}
    errored = {f: records[f] for f in done if records.get(f, {}).get("status") == "error"}

    total = sum(len(d.get("findings", [])) for d in ok.values())
    errtot = sum(len(d.get("findings", [])) for d in errored.values())
    clean = sorted(f for f, d in ok.items() if not d.get("findings"))
    withf = sorted(
        (f for f, d in ok.items() if d.get("findings")),
        key=lambda f: (min(ORDER.get(x.get("severity"), 9) for x in ok[f]["findings"]), f),
    )

    sev = {}
    for d in ok.values():
        for x in d.get("findings", []):
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
        f"{TOTAL_TARGETS - len(done)} of the {TOTAL_TARGETS} target files have not been swept yet.\n"
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
        n = len(ok[f]["findings"])
        print(f"- [`{f}`](#{anchor(f)}) — {n} finding{'s' if n != 1 else ''}")
    if errored:
        print(
            "- [Unreliable results](#unreliable-results) — "
            f"{len(errored)} file{'s' if len(errored) != 1 else ''} recorded as done despite erroring"
        )
    print()

    for f in withf:
        print(f"## `{f}`\n")
        for x in sorted(ok[f]["findings"], key=lambda x: ORDER.get(x.get("severity"), 9)):
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

    if clean:
        print("## Analysed with no findings\n")
        for f in clean:
            print(f"- `{f}`")
        print()

    print("## Not yet analysed\n")
    print(
        f"{TOTAL_TARGETS - len(done)} of the {TOTAL_TARGETS} target files (`src/**/*.php` plus "
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
