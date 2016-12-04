<?php
class mysqlex{
private $mysqli;
	protected $id,$db,$host,$user,$pass,$prefix;
	function __construct($id = null){
		global $_site;
		$this->id = $id ? $id : 'default';
		if($e = $_site->query('/site/mysql/con'.($id ? '[@id="'.$id.'"]' : ''))->item(0)){
			$this->db = $e->getAttribute('db');
			$this->host = $e->getAttribute('host');
			$this->user = $e->getAttribute('user');
			$this->pass = $e->getAttribute('pass');
			$this->prefix = trim($e->getAttribute('pref'));
			$this->connect();
		}else throw new Exception('MySQL connection not found',EXCEPTION_MYSQL);
	}
	function connect(){
		if(isset($_GLOBALS['ap_mysql_ob_'.$this->id]))
			$this->mysqli = $_GLOBALS['ap_mysql_ob_'.$this->id];
		else{
			$_GLOBALS['ap_mysql_ob_'.$this->id] =
			$this->mysqli = new mysqli($this->host, $this->user, $this->pass, $this->db);
			if($this->mysqli->connect_errno)
				throw new Exception($this->mysqli->connect_error,EXCEPTION_MYSQL);
		}
	}
	function query($query){
		$res = $this->mysqli->query($query);
		if(!$res)
			throw new Exception($this->mysqli->error.'<br/><code>'.$query.'</code>',EXCEPTION_MYSQL);
		return $res;
	}
	function getPrefix(){
		return $this->prefix;
	}
	function table($name){
		if($this->getPrefix() && substr($name,0,strlen($this->getPrefix())) != $this->getPrefix())
			$name = $this->getPrefix().$name;
		return $name;
	}
	function getNextId($table){
		if($table
			&& ($rs = $this->query("SHOW TABLE STATUS FROM `{$this->db}` LIKE '{$this->table($table)}'"))
			&& ($r = $rs->fetch_assoc())
		){
			return $r['Auto_increment'];
		}
	}
	function getTables(){
		$arTables = array();
		$res = $this->query('SHOW TABLES FROM `'.$this->db.'`');
		while($r = $res->fetch_assoc())
			$arTables[] = array_pop($r);
		return $arTables;
	}
	function hasTable($name){
		if($arTables = $this->getTables())
			return in_array($this->table($name),$arTables);
	}
	function insert($table,$values){
		return $this->query('insert into `'.$this->table($table).'` (`'.implode('`,`',array_keys($values)).'`) values ('.implode(',',$values).')');
	}
	function update($table,$values,$cond){
		$ar = array();
		foreach($values as $name => $value)
			$ar[] = '`'.$name.'`='.$value;
		return $this->query('update `'.$this->table($table).'` set '.implode(',',$ar).($cond ? ' where '.$cond : null));
	}
	function delete($table,$cond){
		$query = 'DELETE FROM `'.$this->table($table).'`'.($cond ? ' WHERE '.$cond : null);
		return $this->query($query);
	}
	function getInsertId(){
		return $this->mysqli->insert_id;
	}
	function getAffectedRows(){
		return $this->mysqli->affected_rows;
	}
	function fetch($rs){
		if($rs instanceof mysqli_result)
			return $rs->fetch_assoc();
	}
	function getNumRows($rs){
		return $this->mysqli->num_rows;
	}
	static function str($v){
		return '"'.addslashes($v).'"';
	}
	static function num($v){
		$v = str_replace(array(' ',','),array('','.'),trim($v));
		return strstr($v,'.') ? floatval($v) : intval($v);
	}
}