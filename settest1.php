<?php
require_once "RedisCache.class.php" ;

$cacheObj = new RedisCache();


//$cacheObj->set("name", "lixin");




$pdo = new PDO("mysql:host=127.0.0.1;dbname=bbs", "root", "lixinxin");
$pdo->query("set names utf8");
$start = microtime(true);
$statement = $pdo->query("select message from pre_forum_post where message IS NOT NULL and message<>'' limit 50000");
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $_row){
	if(empty($_row['message']))
		continue ;
	
	$cacheObj->set($_row['message'], "a");
	

}
$end = microtime(true);
echo 'insert time ' . (strval($end-$start)) , PHP_EOL ;
$cacheObj->info();



