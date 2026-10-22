#!/bin/sh
set -eu
cd /www/xmqibu-ai
tag=${1:-$(date +%Y%m%d%H%M%S)}
image="xmqibu-ai-app:$tag"
docker build -f ops/deploy/Dockerfile -t "$image" .
printf '%s\n' "$image"
