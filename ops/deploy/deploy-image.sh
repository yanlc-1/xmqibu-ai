#!/bin/sh
set -eu
test "$#" -eq 1 || { echo 'Usage: deploy-image.sh image:tag' >&2; exit 2; }
image=$1
private=/www/xmqibu-ai-private
candidate="xmqibu-ai-candidate-$$"
old_image=''
if docker inspect xmqibu-ai-app >/dev/null 2>&1; then
  old_image=$(docker inspect xmqibu-ai-app --format '{{.Config.Image}}')
fi
cleanup() { docker rm -f "$candidate" >/dev/null 2>&1 || true; }
trap cleanup EXIT HUP INT TERM
docker run -d --name "$candidate" --network nginx-proxy -p 127.0.0.1:19093:80 -v "$private/reader.php:/secrets/reader.php:ro" "$image" >/dev/null
docker network connect xmqibu-ai-private "$candidate"
sleep 2
curl --fail --silent --show-error --max-time 10 http://127.0.0.1:19093/health.json >/dev/null
if [ -n "$old_image" ]; then docker stop xmqibu-ai-app >/dev/null; docker rm xmqibu-ai-app >/dev/null; fi
if ! docker run -d --name xmqibu-ai-app --restart always --network nginx-proxy -p 127.0.0.1:19092:80 -v "$private/reader.php:/secrets/reader.php:ro" "$image" >/dev/null; then
  if [ -n "$old_image" ]; then docker run -d --name xmqibu-ai-app --restart always --network nginx-proxy -p 127.0.0.1:19092:80 -v "$private/reader.php:/secrets/reader.php:ro" "$old_image" >/dev/null; docker network connect xmqibu-ai-private xmqibu-ai-app; fi
  exit 1
fi
docker network connect xmqibu-ai-private xmqibu-ai-app
sleep 2
if ! curl --fail --silent --show-error --max-time 10 http://127.0.0.1:19092/health.json >/dev/null; then
  docker rm -f xmqibu-ai-app >/dev/null
  if [ -n "$old_image" ]; then docker run -d --name xmqibu-ai-app --restart always --network nginx-proxy -p 127.0.0.1:19092:80 -v "$private/reader.php:/secrets/reader.php:ro" "$old_image" >/dev/null; docker network connect xmqibu-ai-private xmqibu-ai-app; fi
  echo 'New image failed health check; previous image restored' >&2
  exit 1
fi
printf '%s\n' "$old_image" > "$private/last-image.txt"
echo "deployed=$image"
