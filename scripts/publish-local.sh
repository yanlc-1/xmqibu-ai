#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
test "$#" -eq 1 && test -f "$1" || { echo 'Usage: publish-local.sh bundle.json' >&2; exit 2; }
test -f data/database.local.php || { echo 'Missing local database config' >&2; exit 1; }
exec php scripts/ingest.php "$1" data/database.local.php
