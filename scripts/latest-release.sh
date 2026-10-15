#!/usr/bin/env bash
# Prints the tag of the latest PHP build release, by version and build number.
set -euo pipefail

gh release list --exclude-drafts --limit 1000 --json tagName --jq '.[].tagName' \
  | grep -E '^php-[0-9]+\.[0-9]+\.[0-9]+(-[0-9]+)?$' \
  | sort -V \
  | tail -n 1
