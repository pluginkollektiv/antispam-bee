#!/usr/bin/env bash
#
# Drive `deepsec process` one file at a time so a long sweep survives the
# Claude subscription session limit.
#
# Running many batches in parallel exhausts the session limit before any batch
# finishes, and a batch that dies mid-investigation banks nothing. One file per
# invocation of the agent means every completed file is durable: the script
# records it and later runs skip it, so the sweep can span many session cycles.
#
# Usage:
#   .deepsec/bin/sweep-one-at-a-time.sh [--max N] [--model M] [--thinking-level L]
#
#   --max N            stop after N files this run (default: until limit is hit)
#   --model M          default: claude-opus-5
#   --thinking-level L default: xhigh
#   --list             print remaining files and exit
#
# Exit codes: 0 = all remaining files done, 2 = stopped by the session limit,
# 1 = usage or setup error.

set -uo pipefail

readonly REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
readonly WORKSPACE="$REPO_ROOT/.deepsec"
readonly STATE_DIR="$WORKSPACE/data/antispam-bee/debug/sweep"
readonly DONE_FILE="$STATE_DIR/done.txt"

MAX=0
MODEL="claude-opus-5"
THINKING="xhigh"
LIST_ONLY=0

while [[ $# -gt 0 ]]; do
	case "$1" in
		--max) MAX="${2:?--max needs a number}"; shift 2 ;;
		--model) MODEL="${2:?--model needs a value}"; shift 2 ;;
		--thinking-level) THINKING="${2:?--thinking-level needs a value}"; shift 2 ;;
		--list) LIST_ONLY=1; shift ;;
		-h|--help) sed -n '2,25p' "${BASH_SOURCE[0]}" | sed 's/^# \?//'; exit 0 ;;
		*) echo "Unknown option: $1" >&2; exit 1 ;;
	esac
done

mkdir -p "$STATE_DIR"
touch "$DONE_FILE"

# The set of files worth investigating: the plugin's own PHP, never vendor or
# tests. Regenerated every run so new files are picked up automatically.
targets() {
	{
		find "$REPO_ROOT/src" -name '*.php' -type f
		[[ -f "$REPO_ROOT/antispam_bee.php" ]] && echo "$REPO_ROOT/antispam_bee.php"
	} | sort
}

mapfile -t PENDING < <(targets | { while IFS= read -r f; do grep -qxF "$f" "$DONE_FILE" || printf '%s\n' "$f"; done; })

total_targets=$(targets | wc -l)
done_count=$(( total_targets - ${#PENDING[@]} ))

if (( LIST_ONLY )); then
	printf 'Done: %d/%d. Remaining:\n' "$done_count" "$total_targets"
	printf '  %s\n' "${PENDING[@]#"$REPO_ROOT/"}"
	exit 0
fi

if (( ${#PENDING[@]} == 0 )); then
	printf 'Nothing left to analyse — all %d file(s) done.\n' "$total_targets"
	exit 0
fi

printf 'Sweep: %d/%d done, %d remaining. Model %s, thinking %s.\n\n' \
	"$done_count" "$total_targets" "${#PENDING[@]}" "$MODEL" "$THINKING"

processed=0
for abs in "${PENDING[@]}"; do
	if (( MAX > 0 && processed >= MAX )); then
		printf '\nReached --max %d, stopping.\n' "$MAX"
		break
	fi

	rel="${abs#"$REPO_ROOT/"}"
	log="$STATE_DIR/$(printf '%s' "$rel" | tr '/' '_').log"

	printf '[%d/%d] %s … ' "$(( done_count + processed + 1 ))" "$total_targets" "$rel"

	# `deepsec process` exits 1 when it produces findings, which is a success
	# for our purposes. Only the session limit is a reason to stop.
	(cd "$WORKSPACE" && pnpm deepsec process \
		--project-id antispam-bee \
		--agent claude \
		--model "$MODEL" \
		--thinking-level "$THINKING" \
		--files "$abs" \
		--concurrency 1 \
		--batch-size 1) >"$log" 2>&1

	if grep -q 'session limit' "$log"; then
		reset_at="$(grep -o 'resets [^)]*' "$log" | head -1)"
		printf 'session limit hit.\n'
		printf '\nStopped: the subscription session limit was reached (%s).\n' "${reset_at:-unknown reset}"
		printf '%d file(s) completed this run. Re-run after the reset to continue.\n' "$processed"
		printf 'Log: %s\n' "$log"
		exit 2
	fi

	findings=$(grep -oE 'Findings: [0-9]+' "$log" | tail -1 | grep -oE '[0-9]+')
	printf 'done (%s finding(s))\n' "${findings:-0}"

	printf '%s\n' "$abs" >>"$DONE_FILE"
	processed=$(( processed + 1 ))
done

printf '\n%d file(s) analysed this run. %d/%d done overall.\n' \
	"$processed" "$(( done_count + processed ))" "$total_targets"
