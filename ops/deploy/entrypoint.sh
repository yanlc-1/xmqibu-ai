#!/bin/sh
set -eu
/usr/local/php7/sbin/www-fpm start
exec /usr/local/nginx/sbin/nginx -g 'daemon off;'
