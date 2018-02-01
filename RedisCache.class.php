<?php


class RedisCache{

	private $cacheList ;
	private $cacheContainer ;
	
	private $vnodeTotal = 4080 ;

	private $fibonacci ;
	private $fibonacciTmpData ;
	private $fibonacciKey ;

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

		foreach ($serverList as $value) {
			$this->r_connect[$value] = new Redis();
			$ip = '' ;
			$port = '' ;
			list($ip, $port) = explode(":", $value);
			$this->r_connect[$value]->connect($ip, $port);
		}

		# 4080
		$vNodeLimit = intval($this->vnodeTotal/count($serverList)) ; // 每个真实节点有4个子节点

		$cacheList = [] ;
		for($i=0; $i<count($serverList); $i++){
			for($j=1; $j<=$vNodeLimit; $j++){
				$vNode = $serverList[$i] . '#'.$j ;
				$vNodeKey = self::hash($vNode) ;

				$cacheList[$vNodeKey] = $vNode ;
			}
			
		}

		ksort($cacheList) ; // 顺时针分布节点到( 1 -- 2^32-1 )圆环
		//var_dump($cacheList);exit();

		$this->cacheList = $cacheList ;

		foreach ($this->cacheList as $key => $value) {
			# code...
			$this->cacheContainer[$key] = [] ;
		}


		// 仅 set_d 斐波那契查找才需要
		$this->createFibonacci() ;
		$this->initFibonacciTmpData();
	}

	public function set($key, $value){
		$node = $this->getNode($key) ;
		//echo 'get node ok : ', $node, PHP_EOL ;
		
		$real = '' ;
		$virtual = '' ;
		list($real, $virtual) = explode("#", $node) ;

		$this->r_connect[$real]->set("ht_".md5($key), $value);
	}

	public function get($key){
		$node = $this->getNode($key) ;
		//echo 'get node ok : ', $node, PHP_EOL ;
		$real = '' ;
		$virtual = '' ;
		list($real, $virtual) = explode("#", $node) ;

		$value = $this->r_connect[$real]->get("ht_".md5($key));
		return $value ;
	}

	public static function hash($str){
		return intval(crc32(md5($str))) ;
	}

	public function info(){
		//var_dump($this->cacheContainer);
		$ipcount = [] ;
		$total = 0 ;
		foreach ($this->cacheContainer as $key => $value) {
			$c = count($value) ;
			# code...
			// echo "{$key}\t{$this->cacheList[$key]}\t".$c , PHP_EOL ;

			$t = explode("#", $this->cacheList[$key]) ;

			$ipcount[$t[0]] = isset($ipcount[$t[0]]) ? $ipcount[$t[0]] + $c : $c;
			$total+=$c ;
		}

		var_dump($ipcount) ;
		var_dump($total) ;
	}

	private function createFibonacci(){
		$fibonacci[] = 1 ;
		$fibonacci[] = 1 ;
		
		for($i=2; $i<22; $i++){
			$fibonacci[$i] = $fibonacci[$i-1] + $fibonacci[$i-2] ;
		}
		// var_dump($fibonacci) ; exit();
		$this->fibonacci = $fibonacci ;
	}

	private function initFibonacciTmpData(){
		$cacheList = array_keys($this->cacheList) ;
		$cacheListLen = count($cacheList) ;
		$fibonacci = $this->fibonacci ;

		$fKey = 0 ;
		while($cacheListLen>$fibonacci[$fKey]-1){
			$fKey++ ;
		}

		$clTmp = [] ;
		$clTmp = $cacheList ;
		for($i=$cacheListLen; $i<$fibonacci[$fKey]-1; $i++){
			$clTmp[] = $cacheList[$cacheListLen-1] ; // 用最大值填充数组
		}

		$this->fibonacciKey = $fKey ;
		$this->fibonacciTmpData = $clTmp ;
	}

	// 获取节点配置
	public function getNode($str){
		$hash = self::hash($str) ;
		
		$_cacheList = array_keys($this->cacheList) ;
		$lastKey =array_pop($_cacheList);
		if ($hash >= $lastKey) {
			//$this->cacheContainer[$lastKey][] = ['hash'=>$hash, 'data'=>$str] ;
			return $this->cacheList[$lastKey] ;
		}

		$cacheList = array_keys($this->cacheList) ;
		$cacheListLen = count($cacheList) ;

		// 创建斐波那契数列
		$fibonacci = $this->fibonacci ;


		$low=0 ;
		$high = $cacheListLen -1;
		$hotNode = null ;

		$fKey = $this->fibonacciKey ;
		

		$clTmp = $this->fibonacciTmpData ;
		

		while($low<=$high){
			
			$mid = $low+$fibonacci[$fKey-1]-1 ;
			
			if(($hash < $clTmp[$mid]) && ($mid-1>=0 && $hash < $clTmp[$mid-1])){
				//left
				$high = $mid -1 ;
				$fKey-=1;
			}else if($hash >= $clTmp[$mid]) {
				//right
				$low = $mid + 1 ;
				$fKey-=2;
			}else{
				if ($mid<$cacheListLen) {
					$hotNode = $cacheList[$mid] ;
					break;
				}else{
					$hotNode = $cacheList[$cacheListLen-1] ;	
					// echo 'b',PHP_EOL ;
					break;
				}
			}

		}
		
		if (!$hotNode) {
			
			echo 'hot node is not exist '.var_export($hotNode, 1) , PHP_EOL ;
			return false ;
		}

		//$this->cacheContainer[$hotNode][] = ['hash'=>$hash, 'data'=>$str] ;
		//echo 'lala ', $hotNode, PHP_EOL ;
		return $this->cacheList[$hotNode];
	}
}


