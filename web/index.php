<?php
require __DIR__.'/../src/validate.php';
require __DIR__.'/../src/render.php';
require __DIR__.'/../src/web.php';
try{
 if(!is_readable('/secrets/reader.php'))throw new RuntimeException('Database reader config unavailable');
 $config=require '/secrets/reader.php';
 $db=new PDO($config['dsn'],$config['user'],$config['password'],array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false));
 $path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
 $response=renderRequest($db,$path);
 http_response_code($response['status']);
 header('Content-Type: '.$response['contentType']);
 header('Cache-Control: no-cache');
 echo $response['body'];
}catch(Throwable $error){
 error_log('xmqibu-ai read failed: '.get_class($error));
 http_response_code(503);
 header('Content-Type: text/html; charset=utf-8');
 header('Cache-Control: no-store');
 echo '<!doctype html><html lang="zh-CN"><meta charset="utf-8"><title>暂时无法读取 · 启步 AI</title><body><h1>内容暂时无法读取</h1><p>请稍后再试。</p></body></html>';
}
