#!/usr/bin/env bash
#
# Run PHPCS, write a checkstyle report, and emit GitHub PR annotations via
# cs2pr. Runs cs2pr even when phpcs fails (preserving inline annotations).
# When phpcs fails its exit code is returned; when phpcs succeeds, cs2pr's
# exit code is returned.

set -uo pipefail

composer run phpcs -- --report-checkstyle=phpcs-report.xml
phpcs_exit=$?

"$(composer global config home)/vendor/bin/cs2pr" phpcs-report.xml
cs2pr_exit=$?

if [ "$phpcs_exit" -ne 0 ]; then
  exit "$phpcs_exit"
fi

exit "$cs2pr_exit"
