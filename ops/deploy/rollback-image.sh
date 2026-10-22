#!/bin/sh
set -eu
image=$(cat /www/xmqibu-ai-private/last-image.txt)
test -n "$image" || { echo 'No previous image recorded' >&2; exit 1; }
exec /www/xmqibu-ai/ops/deploy/deploy-image.sh "$image"
