#!/bin/sh
set -eu
test "$#" -gt 0 || { echo 'Usage: with-db-tunnel.sh command...' >&2; exit 2; }
port=$(python3 -c 'import socket; s=socket.socket(); s.bind(("127.0.0.1",0)); print(s.getsockname()[1]); s.close()')
socket=$(mktemp /tmp/xmqibu-ai-ssh.XXXXXX)
rm -f "$socket"
cleanup() {
  ssh -S "$socket" -O exit -p 34185 -o BatchMode=yes -o UpdateHostKeys=no root@111.231.204.222 >/dev/null 2>&1 || true
  rm -f "$socket"
}
trap cleanup EXIT HUP INT TERM
ssh -f -N -M -S "$socket" -L "127.0.0.1:$port:127.0.0.1:4437" -p 34185 -o BatchMode=yes -o UpdateHostKeys=no -o ExitOnForwardFailure=yes root@111.231.204.222
export XMQIBU_AI_DB_PORT="$port"
"$@"
