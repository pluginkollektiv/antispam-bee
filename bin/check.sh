#!/usr/bin/env bash
#
# Run every check that needs nothing but PHP and Composer, in one command.
#
# These are the checks behind the `Static analysis` and `Unit tests` workflows. The
# integration, E2E and Plugin Check suites need a running `wp-env`, so they are not
# included here — see the table in `AGENTS.md` for those.
#
# Two things this deliberately does differently from a Composer script list:
#
#   * It does not stop at the first failure. Running the checks one by one, the cost is
#     not the typing, it is that each failure hides the next one. Every check runs, and
#     the summary at the end reports all of them.
#   * It skips a check whose tool is not installed, and says so, rather than failing.
#     That keeps the script usable after `composer install --no-dev` and on branches
#     where a tool has not been added yet.
#
# Each check runs the same Composer script that CI runs, so there is one definition of
# what a check is and this script cannot drift from the workflows.
#
# Exits non-zero if any check failed.

set -uo pipefail

cd "$( dirname "${BASH_SOURCE[0]}" )/.." || exit 1

if [ -t 1 ]; then
	bold=$'\033[1m'
	dim=$'\033[2m'
	red=$'\033[31m'
	green=$'\033[32m'
	yellow=$'\033[33m'
	reset=$'\033[0m'
else
	bold='' dim='' red='' green='' yellow='' reset=''
fi

names=()
states=()
failures=0

# Is a script of this name defined in composer.json?
composer_has_script() {
	php -r 'exit( (int) ! isset( json_decode( file_get_contents( "composer.json" ), true )["scripts"][ $argv[1] ] ) );' "$1"
}

# record <name> <state>
record() {
	names+=( "$1" )
	states+=( "$2" )
}

# check <label> <vendor binary> <composer script>
check() {
	local label=$1 binary=$2 script=$3

	if ! composer_has_script "$script"; then
		printf '\n%s▶ %s%s %s— skipped, no "%s" script in composer.json%s\n' \
			"$bold" "$label" "$reset" "$dim" "$script" "$reset"
		record "$label" skip
		return
	fi

	if [ ! -x "vendor/bin/$binary" ]; then
		printf '\n%s▶ %s%s %s— skipped, vendor/bin/%s is not installed%s\n' \
			"$bold" "$label" "$reset" "$dim" "$binary" "$reset"
		record "$label" skip
		return
	fi

	printf '\n%s▶ %s%s\n' "$bold" "$label" "$reset"

	if composer --no-interaction "$script"; then
		record "$label" pass
	else
		record "$label" fail
		failures=$(( failures + 1 ))
	fi
}

check 'Coding standards'    phpcs    cs
check 'Static analysis'     phpstan  phpstan
check 'Rector'              rector   rector
check 'Unit tests'          phpunit  test:unit
check 'WordPress functions' wp-since wp-since

printf '\n%s── Summary ──%s\n' "$bold" "$reset"

for i in "${!names[@]}"; do
	case "${states[$i]}" in
		pass) printf '  %s✓%s  %s\n' "$green" "$reset" "${names[$i]}" ;;
		fail) printf '  %s✗%s  %s\n' "$red" "$reset" "${names[$i]}" ;;
		skip) printf '  %s–%s  %s %s(skipped)%s\n' "$yellow" "$reset" "${names[$i]}" "$dim" "$reset" ;;
	esac
done

if [ "$failures" -gt 0 ]; then
	printf '\n%s%s of %s checks failed.%s Fix them with `composer csfix` and `composer rector:fix` where applicable.\n' \
		"$red" "$failures" "${#names[@]}" "$reset"
	exit 1
fi

printf '\n%sAll checks passed.%s\n' "$green" "$reset"
