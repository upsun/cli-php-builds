#!/usr/bin/env bash
# Compares the sources of a release with the latest versions that
# static-php-cli would download, and prints any differences.
# Usage: check-sources.sh <spc binary> <release tag>
set -euo pipefail

spc=$(realpath "$1")
tag=$2
minor=$(echo "$tag" | sed -E 's/^php-([0-9]+\.[0-9]+)\..*/\1/')
repo=${GH_REPO:-$(gh repo view --json nameWithOwner --jq .nameWithOwner)}

workdir=$(mktemp -d)
trap 'rm -rf "$workdir"' EXIT
cd "$workdir"

# Releases from before sources.json was recorded are rebuilt.
assets=$(gh release view "$tag" --repo "$repo" --json assets --jq '.assets[].name')
if ! grep -qx sources.json <<<"$assets"; then
  echo "$tag: no sources.json"
  exit
fi
gh release download "$tag" --repo "$repo" --pattern sources.json

sources=$(jq -r '[.[] | keys[]] | unique | join(",")' sources.json)
"$spc" download "$sources" --with-php="$minor" >&2

jq -r --slurpfile latest downloads/.lock.json '
  .[] | to_entries[]
  | {recorded: .value, latest: ($latest[0][.key].filename // "missing")}
  | select(.recorded != .latest)
  | "\(.recorded) -> \(.latest)"
' sources.json | sort -u
