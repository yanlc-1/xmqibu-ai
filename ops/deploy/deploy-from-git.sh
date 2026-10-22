#!/bin/sh
set -eu
cd /www/xmqibu-ai
exec 9>/www/xmqibu-ai-private/deploy.lock
flock -n 9 || exit 0
if ! git diff --quiet || ! git diff --cached --quiet; then echo 'Local source changes block deployment' >&2; exit 1; fi
git fetch -q origin main
current=$(git rev-parse HEAD)
target=$(git rev-parse FETCH_HEAD)
[ "$current" != "$target" ] || { docker inspect xmqibu-ai-app >/dev/null 2>&1 && exit 0; }
if [ "$current" != "$target" ]; then git checkout -q --detach "$target"; fi
build_log=/tmp/xmqibu-ai-build-$$.log
if ! ./ops/deploy/build-image.sh "prod-$(date +%Y%m%d%H%M%S)" > "$build_log" 2>&1; then
  cat "$build_log" >&2
  git checkout -q --detach "$current"
  exit 1
fi
image=$(tail -n 1 "$build_log")
if ! ./ops/deploy/deploy-image.sh "$image"; then
  git checkout -q --detach "$current"
  echo 'Deployment failed; restored previous source and website' >&2
  exit 1
fi
echo "deployed_commit=$target"
