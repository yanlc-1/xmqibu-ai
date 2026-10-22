#!/bin/sh
set -eu
test "$#" -eq 1 || { echo 'Usage: deploy-image.sh image:tag' >&2; exit 2; }
image=$1
private=/www/xmqibu-ai-private
candidate="xmqibu-ai-candidate-$$"
old_image=''
run_app() {
  container_name=$1; local_port=$2; restart=$3; selected_image=$4
  set -- -d --name "$container_name" --network nginx-proxy -p "127.0.0.1:$local_port:80" -v "$private/reader.php:/secrets/reader.php:ro"
  if [ -f "$private/ca.pem" ]; then set -- "$@" -v "$private/ca.pem:/secrets/ca.pem:ro"; fi
  if [ "$restart" = yes ]; then set -- "$@" --restart always; fi
  docker run "$@" "$selected_image" >/dev/null
}
if docker inspect xmqibu-ai-app >/dev/null 2>&1; then
  old_image=$(docker inspect xmqibu-ai-app --format '{{.Config.Image}}')
fi
cleanup() { docker rm -f "$candidate" >/dev/null 2>&1 || true; }
trap cleanup EXIT HUP INT TERM
run_app "$candidate" 19093 no "$image"
sleep 2
curl --fail --silent --show-error --max-time 10 http://127.0.0.1:19093/health.json >/dev/null
if [ -n "$old_image" ]; then docker stop xmqibu-ai-app >/dev/null; docker rm xmqibu-ai-app >/dev/null; fi
if ! run_app xmqibu-ai-app 19092 yes "$image"; then
  if [ -n "$old_image" ]; then run_app xmqibu-ai-app 19092 yes "$old_image"; fi
  exit 1
fi
sleep 2
if ! curl --fail --silent --show-error --max-time 10 http://127.0.0.1:19092/health.json >/dev/null; then
  docker rm -f xmqibu-ai-app >/dev/null
  if [ -n "$old_image" ]; then run_app xmqibu-ai-app 19092 yes "$old_image"; fi
  echo 'New image failed health check; previous image restored' >&2
  exit 1
fi
printf '%s\n' "$old_image" > "$private/last-image.txt"
echo "deployed=$image"
