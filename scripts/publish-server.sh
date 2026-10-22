#!/bin/sh
set -eu
case "$1" in /www/xmqibu-ai/data/*.json) ;; *) echo 'Invalid bundle path' >&2; exit 2;; esac
exec flock -x /www/xmqibu-ai/site/.deploy.lock docker run --rm --network xmqibu-ai-private -v /www/xmqibu-ai:/site -v /www/xmqibu-ai-private:/secrets:ro --entrypoint php php7.1-nginx-1:v1 /site/scripts/publish.php "/site/data/$(basename "$1")" /secrets/config.php
