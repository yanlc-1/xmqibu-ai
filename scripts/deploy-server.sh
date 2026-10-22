#!/bin/sh
set -eu
cd /www/xmqibu-ai
exec 9>site/.deploy.lock
flock -n 9 || exit 0
git fetch -q origin main
current=$(git rev-parse HEAD)
target=$(git rev-parse FETCH_HEAD)
[ "$current" != "$target" ] || exit 0
if ! git diff --quiet || ! git diff --cached --quiet; then
  echo 'Deployment refused: server source has local changes' >&2
  exit 1
fi
git checkout -q --detach "$target"
if ! docker run --rm --network xmqibu-ai-private -v /www/xmqibu-ai:/site -v /www/xmqibu-ai-private:/secrets:ro --entrypoint php php7.1-nginx-1:v1 /site/scripts/rebuild.php /secrets/config.php; then
  git checkout -q --detach "$current"
  echo 'Deployment failed; restored previous code and retained last published pages' >&2
  exit 1
fi
echo "Deployed $target"
