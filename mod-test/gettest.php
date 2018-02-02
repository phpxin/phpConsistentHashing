<?php
require_once "ModCache.class.php" ;

$total = 0 ;
$succ = 0 ;
$miss = 0 ;

$cacheObj = new ModCache();


$pdo = new PDO("mysql:host=127.0.0.1;dbname=bbs", "root", "lixinxin");
$pdo->query("set names utf8");
$start = microtime(true);
$statement = $pdo->query("select message from pre_forum_post where message IS NOT NULL and message<>'' limit 50000");
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $_row){
	if(empty($_row['message']))
		continue ;
	
	$flag = $cacheObj->get($_row['message']);
	
	$total +=1 ;
	if ($flag) {
		$succ += 1;
	}else{
		$miss += 1 ;
	}

} 
$end = microtime(true);
echo 'gets time ' . (strval($end-$start)) , PHP_EOL ;
echo 'total is '.$total.' succ is '.$succ.' miss is '.$miss.' hits '.(($succ/$total)*100).' % '.PHP_EOL ;




