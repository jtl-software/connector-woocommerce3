#!/usr/bin/env bash
#
# Bump the plugin version for a release.
#
# Usage: bin/bump-version.sh <x.y.z>
#
# Updates:
#   - build-config.yaml      version:
#   - woo-jtl-connector.php  plugin header "Version:"
#   - readme.txt             "Stable tag:" and a draft "= x.y.z =" changelog section
#   - CHANGELOG.md           entries under "## Unreleased" move to "## x.y.z _YYYY-MM-DD_"
#
# All checks (including write permissions) run before any file is written.
# The script aborts if the version already exists, so running it twice with
# the same version changes nothing.
# Line endings (LF/CRLF) of each file are preserved.

set -euo pipefail

die() {
  echo "Error: $*" >&2
  exit 1
}

if [ "$#" -ne 1 ]; then
  echo "Usage: $0 <x.y.z>" >&2
  exit 1
fi

version="$1"
if ! [[ "$version" =~ ^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$ ]]; then
  die "invalid version '$version', expected x.y.z (e.g. 2.5.1)"
fi

cd "$(dirname "${BASH_SOURCE[0]}")/.."

build_config="build-config.yaml"
plugin_file="woo-jtl-connector.php"
readme="readme.txt"
changelog="CHANGELOG.md"
release_date="$(date +%Y-%m-%d)"

for file in "$build_config" "$plugin_file" "$readme" "$changelog"; do
  [ -f "$file" ] || die "$file not found"
  [ -w "$file" ] || die "$file is not writable"
done

# Count lines matching an extended regex, ignoring a trailing CR.
count_matches() {
  tr -d '\r' < "$2" | grep -cE "$1" || true
}

require_single() {
  local count
  count="$(count_matches "$1" "$2")"
  [ "$count" -eq 1 ] || die "expected exactly one line matching '$1' in $2, found $count"
}

require_single '^version: ' "$build_config"
require_single '^ \* Version: ' "$plugin_file"
require_single '^Stable tag: ' "$readme"
require_single '^== Changelog ==$' "$readme"
require_single '^## Unreleased$' "$changelog"

escaped_version="${version//./\\.}"
[ "$(count_matches "^## ${escaped_version}( |$)" "$changelog")" -eq 0 ] \
  || die "version $version already exists in $changelog"
[ "$(count_matches "^= ${escaped_version} =$" "$readme")" -eq 0 ] \
  || die "version $version already exists in $readme"

current_version="$(tr -d '\r' < "$build_config" | sed -n 's/^version: *//p')"
[ "$current_version" != "$version" ] || die "version $version is already set in $build_config"

tmp_dir="$(mktemp -d)"
trap 'rm -rf "$tmp_dir"' EXIT

# Replace the value after a line prefix (first match only), keeping the EOL style.
replace_value() {
  local prefix="$1" file="$2"
  awk -v prefix="$prefix" -v value="$version" '
    {
      eol = sub(/\r$/, "") ? "\r" : ""
      if (!done && index($0, prefix) == 1) {
        $0 = prefix value
        done = 1
      }
      printf "%s%s\n", $0, eol
    }
  ' "$file" > "$tmp_dir/$file"
}

replace_value 'version: ' "$build_config"
replace_value ' * Version: ' "$plugin_file"

# CHANGELOG.md: move the "## Unreleased" entries into a new version section and
# write the raw entries to $tmp_dir/entries for readme.txt.
status=0
awk -v heading="## $version _${release_date}_" -v entries_file="$tmp_dir/entries" '
  function flush(    first, last, i) {
    first = 1
    last = n
    while (first <= last && lines[first] ~ /^[ \t]*$/) first++
    while (last >= first && lines[last] ~ /^[ \t]*$/) last--
    count = last - first + 1
    if (count <= 0) {
      return
    }
    printf "%s\n%s%s\n", eol, heading, eol
    for (i = first; i <= last; i++) {
      printf "%s%s\n", lines[i], eol
      print lines[i] > entries_file
    }
    printf "%s\n", eol
  }
  {
    eol = sub(/\r$/, "") ? "\r" : ""
  }
  state == 0 {
    printf "%s%s\n", $0, eol
    if ($0 == "## Unreleased") {
      state = 1
    }
    next
  }
  state == 1 && /^## / {
    flush()
    state = 2
  }
  state == 1 {
    lines[++n] = $0
    next
  }
  {
    printf "%s%s\n", $0, eol
  }
  END {
    if (state == 1) {
      flush()
    }
    if (count <= 0) {
      exit 3
    }
  }
' "$changelog" > "$tmp_dir/$changelog" || status=$?
if [ "$status" -eq 3 ]; then
  die "no entries found under '## Unreleased' in $changelog"
elif [ "$status" -ne 0 ]; then
  die "failed to update $changelog"
fi

# Convert CHANGELOG entries ("- CO-1234 - text") into readme draft entries
# ("* Feature: text"). Continuation lines are joined to the previous entry.
awk '
  /^[-*][ \t]+/ {
    if (entry != "") print "* Feature: " entry
    entry = $0
    sub(/^[-*][ \t]+/, "", entry)
    sub(/^[A-Z][A-Z0-9]*-[0-9]+[ \t]+-[ \t]+/, "", entry)
    next
  }
  /[^ \t]/ {
    line = $0
    sub(/^[ \t]+/, "", line)
    entry = (entry == "") ? line : entry " " line
  }
  END {
    if (entry != "") print "* Feature: " entry
  }
' "$tmp_dir/entries" > "$tmp_dir/readme-entries"

# readme.txt: set "Stable tag" and prepend the draft changelog section.
awk -v version="$version" -v entries_file="$tmp_dir/readme-entries" '
  function print_section() {
    printf "%s\n= %s =%s\n", eol, version, eol
    while ((getline entry < entries_file) > 0) {
      printf "%s%s\n", entry, eol
    }
    printf "%s\n", eol
  }
  {
    eol = sub(/\r$/, "") ? "\r" : ""
  }
  pending {
    pending = 0
    print_section()
    if ($0 ~ /^[ \t]*$/) {
      next
    }
  }
  index($0, "Stable tag: ") == 1 {
    $0 = "Stable tag: " version
  }
  {
    printf "%s%s\n", $0, eol
  }
  $0 == "== Changelog ==" {
    pending = 1
  }
  END {
    if (pending) {
      print_section()
    }
  }
' "$readme" > "$tmp_dir/$readme"

# Write all files only after every transformation succeeded.
for file in "$build_config" "$plugin_file" "$readme" "$changelog"; do
  cat "$tmp_dir/$file" > "$file"
done

echo "Bumped version ${current_version} -> ${version} (${release_date})."
echo "Next steps:"
echo "  - review the '= ${version} =' section in ${readme} and adjust the Feature/Security/Fix/Update prefixes"
echo "  - check the changes with: git diff"
