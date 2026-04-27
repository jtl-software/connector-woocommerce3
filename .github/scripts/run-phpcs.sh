#!/usr/bin/env bash
#
# Run PHPCS, write a checkstyle report, and emit GitHub PR annotations via
# cs2pr. Captures both exit codes so a phpcs failure does not skip cs2pr
# (which would lose the inline annotations) and a cs2pr failure does not
# get swallowed by a successful phpcs.

set -uo pipefail

composer run phpcs -- --report-checkstyle=phpcs-report.xml
phpcs_exit=$?

"$(composer global config home)/vendor/bin/cs2pr" phpcs-report.xml
cs2pr_exit=$?

exit $(( phpcs_exit + cs2pr_exit ))
