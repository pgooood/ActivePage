<?
class config{
private $ar;
function __construct($ar = null){
	$this->ar = is_array($ar) ? $ar : array();
}
function __call($m,$a){
	switch($m){
		default:
			if(preg_match('/^get(\w+)$/',$m,$res) && isset($this->ar[$name = strtolower($res[1])]))
				return $this->ar[$name];
			elseif(preg_match('/^set(\w+)$/',$m,$res))
				return $this->ar[strtolower($res[1])] = $a[0];
	}
}
}
?>