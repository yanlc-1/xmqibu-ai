#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
exec ./scripts/publish-local.sh "$@"
