<?php
function openDatabase($config){
 if(!is_array($config)||!isset($config['dsn'],$config['user'],$config['password']))throw new RuntimeException('Database connection config is incomplete');
 $options=array(PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false);
 if(isset($config['options'])){
  if(!is_array($config['options']))throw new RuntimeException('Database options must be an array');
  $options=$config['options']+$options;
 }
 $options[PDO::ATTR_ERRMODE]=PDO::ERRMODE_EXCEPTION;
 $options[PDO::ATTR_EMULATE_PREPARES]=false;
 return new PDO($config['dsn'],$config['user'],$config['password'],$options);
}
