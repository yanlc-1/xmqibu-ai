#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
test -f "$1"
php -n -r 'require "src/validate.php"; validateBundle(json_decode(file_get_contents($argv[1]), true));' "$1"
file="incoming-$(date +%Y%m%d%H%M%S)-$$.json"
scp -q -P 34185 -o BatchMode=yes -o UpdateHostKeys=no "$1" "root@111.231.204.222:/www/xmqibu-ai/data/$file"
ssh -o BatchMode=yes -o UpdateHostKeys=no -p 34185 root@111.231.204.222 "/www/xmqibu-ai/scripts/publish-server.sh /www/xmqibu-ai/data/$file"
curl --fail --silent --show-error --max-time 20 https://xmqibu.com/health.json
