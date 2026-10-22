FROM php7.1-nginx-1:v1
WORKDIR /app
COPY src /app/src
COPY web /app/web
COPY public/style.css /app/web/style.css
COPY public/app.js /app/web/app.js
COPY ops/deploy/nginx-site.conf /usr/local/nginx/conf/server/xmqibu-ai.conf
COPY ops/deploy/entrypoint.sh /app/entrypoint.sh
RUN chmod 755 /app/entrypoint.sh \
    && printf '\nsession.upload_progress.enabled=Off\nfile_uploads=Off\nenable_post_data_reading=Off\ncgi.fix_pathinfo=0\nexpose_php=Off\ndisplay_errors=Off\nlog_errors=On\n' >> /usr/local/php7/etc/php.ini
EXPOSE 80
ENTRYPOINT ["/app/entrypoint.sh"]
