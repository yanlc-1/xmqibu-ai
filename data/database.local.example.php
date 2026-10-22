<?php
// Copy to data/database.local.php and fill in your local collector's DB connection.
// The DB account needs SELECT, INSERT, UPDATE and DELETE on xmqibu-ai.
return array(
    'dsn' => 'mysql:host=YOUR_DB_HOST;port=YOUR_DB_PORT;dbname=xmqibu-ai;charset=utf8mb4',
    'user' => 'YOUR_WRITER_USER',
    'password' => 'YOUR_WRITER_PASSWORD',
    // For a remote DB, configure TLS if supported by your provider:
    // 'options' => array(PDO::MYSQL_ATTR_SSL_CA => '/absolute/path/to/ca.pem'),
);
