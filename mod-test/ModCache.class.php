<?php
/*
节点数：3，4，5，6
分布式算法：取模运算
php7 5w数据 插入时间 insert time 2.506796836853 

*/

class ModCache{

	private $cacheList ;
	private $cacheContainer ;
	
	private $nodeTotal = 0 ;

	private $r_connect = [] ;

	public function __construct(){
		$serverList = [
			'127.0.0.1:6374' ,
			'127.0.0.1:6375' ,
			'127.0.0.1:6376' ,
			'127.0.0.1:6377' ,
			'127.0.0.1:6378' ,
			'127.0.0.1:6379' 
		] ;

		$this->nodeTotal = count($serverList);

		
		for ($i=0;$i<$this->nodeTotal;$i++){
			$this->r_connect[$i] = new Redis();
			$ip = '' ;
			$port = '' ;
			list($ip, $port) = explode(":", $serverList[$i]);
			$this->r_connect[$i]->connect($ip, $port);
		}

	}

	public static function hash($str){
		return intval(crc32(md5($str))) ;
	}

	public function set($key, $value){
		$node = $this->getNode($key) ;
		$this->r_connect[$node]->set("ht_".md5($key), $value);
	}

	public function get($key){
		$node = $this->getNode($key) ;
		$value = $this->r_connect[$node]->get("ht_".md5($key));
		return $value ;
	}

	// 获取节点配置
	public function getNode($str){
		$hash = self::hash($str) ;
		$hotNode = $hash % $this->nodeTotal ;
		return $hotNode;
	}
}


