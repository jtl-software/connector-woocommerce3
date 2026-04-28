#!/usr/bin/env bash
#
# Open a draft pull request for the currently pushed branch.
# - Derives the PR title from the first line of the head commit message if it
#   starts with "Title:" or "Titel:" (case-insensitive),
#   otherwise falls back to "Draft: <branch>".
# - Idempotent: if an open PR with the same head/base already exists, prints a
#   skip message and exits 0 without creating a duplicate.
#
# Usage:
#   auto-draft-pr.sh <commit-message> <branch> <default-branch> <actor> <repo>
#
# Required environment:
#   GH_TOKEN: authentication token for the `gh` CLI (read from the process
#              environment by `gh` itself; not passed as an argument to avoid
#              leaking into process listings).

set -euo pipefail

if [ "$#" -ne 5 ]; then
  echo "Usage: $(basename "$0") <commit-message> <branch> <default-branch> <actor> <repo>" >&2
  exit 2
fi

COMMIT_MSG="$1"
BRANCH="$2"
DEFAULT_BRANCH="$3"
ACTOR="$4"
REPO="$5"

# skip on protected branches
BRANCH_ENCODED="$(printf '%s' "$BRANCH" | jq -sRr @uri)"
IS_PROTECTED="$(gh api "repos/$REPO/branches/$BRANCH_ENCODED" --jq '.protected')"
if [ "$IS_PROTECTED" = "true" ]; then
  echo "Branch $BRANCH is protected, skipping auto-draft-PR."
  exit 0
fi

SUBJECT="$(printf '%s\n' "$COMMIT_MSG" | head -n1)"
if printf '%s' "$SUBJECT" | grep -Eiq '^(title|titel):[[:space:]]*.+'; then
  TITLE="$(printf '%s' "$SUBJECT" | sed -E 's/^([Tt][Ii][Tt][Ll][Ee]|[Tt][Ii][Tt][Ee][Ll]):[[:space:]]*//')"
else
  TITLE="Draft: $BRANCH"
fi

EXISTING="$(gh pr list --repo "$REPO" --head "$BRANCH" --base "$DEFAULT_BRANCH" --state open --json number --jq '.[0].number // empty')"
if [ -n "$EXISTING" ]; then
  echo "PR #$EXISTING already exists for $BRANCH, skipping."
  exit 0
fi

gh pr create --repo "$REPO" --draft --base "$DEFAULT_BRANCH" --head "$BRANCH" --title "$TITLE" --assignee "$ACTOR"
