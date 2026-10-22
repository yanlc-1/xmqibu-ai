set -eu
base=/www/xmqibu-ai
private=/www/xmqibu-ai-private
mkdir -p "$base/site/releases" "$base/data" "$private" "$private/mysql"
chmod 700 "$private"
if [ ! -f "$private/mysql.env" ]; then
python - <<'PY'
import os,binascii,json
p='/www/xmqibu-ai-private/'
root=binascii.hexlify(os.urandom(24)); user=binascii.hexlify(os.urandom(24))
open(p+'mysql.env','w').write('MYSQL_ROOT_PASSWORD='+root+'\nMYSQL_DATABASE=xmqibu_ai\nMYSQL_USER=xmqibu_ai\nMYSQL_PASSWORD='+user+'\n')
open(p+'config.php','w').write("<?php return array('dsn'=>'mysql:host=xmqibu-ai-mysql;dbname=xmqibu_ai;charset=utf8mb4','user'=>'xmqibu_ai','password'=>'"+user+"','output'=>'/site/site');\n")
os.chmod(p+'mysql.env',0600);os.chmod(p+'config.php',0600)
PY
fi
if ! docker network inspect xmqibu-ai-private >/dev/null 2>&1; then docker network create --internal xmqibu-ai-private >/dev/null; fi
if ! docker inspect xmqibu-ai-mysql >/dev/null 2>&1; then
 docker run -d --name xmqibu-ai-mysql --restart unless-stopped --network xmqibu-ai-private --memory 384m --env-file "$private/mysql.env" -v "$private/mysql:/var/lib/mysql" fed8e8a00003 --innodb-buffer-pool-size=64M --max-connections=20 --performance-schema=OFF >/dev/null
fi
if [ ! -f "$private/original-site.tar.gz" ]; then tar -czf "$private/original-site.tar.gz" -C /www xmqibu; chmod 600 "$private/original-site.tar.gz"; cp /usr/local/nginx/conf/vhost/xmqibu-https.conf "$private/xmqibu-https.conf.original"; fi
printf 'isolated-database-created; original-site-backed-up\n'
