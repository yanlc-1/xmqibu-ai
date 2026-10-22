<?php
// Install at /www/xmqibu-ai-private/reader.php on the server.
// The account should have only SELECT on xmqibu-ai. HOST must be reachable
// from the xmqibu-ai-app Docker container, not just from the server shell.
return array(
    'dsn' => 'mysql:host=YOUR_DB_HOST;port=YOUR_DB_PORT;dbname=xmqibu-ai;charset=utf8mb4',
    'user' => 'YOUR_READER_USER',
    'password' => 'YOUR_READER_PASSWORD',
    // 'options' => array(PDO::MYSQL_ATTR_SSL_CA => '/secrets/ca.pem'),
);
