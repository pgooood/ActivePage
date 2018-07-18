<?php
class mysql{
protected static $cache;
protected $mysql,$id,$e;

function __construct($id = null){
	global $_site;
	
	$this->id = $id;
	$cacheIndex = $id ? $id : 'default';
	
	//смотрим в кэш соединений
	if(!is_array(self::$cache))
		self::$cache = array();
	if(!empty(self::$cache[$cacheIndex])){
		$this->mysql = self::$cache[$cacheIndex]['mysql'];
		return;
	}
	
	//если в кэше нет, то соединяемся
	if($e = $_site->query('/site/mysql/con'.($id ? '[@id="'.$id.'"]' : ''))->item(0)){
		$this->connect();
		self::$cache[$cacheIndex]['mysql'] = $this->mysql;
	}else throw new Exception('MySQL connection not found',EXCEPTION_MYSQL);
}
protected function e(){
	global $_site;
	if(!$this->e)
		$this->e = $_site->query('/site/mysql/con'.($this->id ? '[@id="'.$this->id.'"]' : ''))->item(0);
	return $this->e;
}
function getDb(){
	return $this->e()->getAttribute('db');
}
function getHost(){
	return $this->e()->getAttribute('host');
}
function getUser(){
	return $this->e()->getAttribute('user');
}
function getPass(){
	return $this->e()->getAttribute('pass');
}
function getPrefix(){
	return $this->e()->getAttribute('pref');
}
function getCharset(){
	return  $this->e()->hasAttribute('charset')
		? $this->e()->getAttribute('charset')
		: 'utf8';
}
function connect(){
	$this->mysql = new mysqli($this->getHost(),$this->getUser(),$this->getPass(),$this->getDb());
	if($this->mysql->connect_errno)
		throw new Exception('MySQL connection error:<br/><code>'.$this->mysql->connect_error.'</code>',EXCEPTION_MYSQL);
	if(!$this->mysql->set_charset($this->getCharset()))
		throw new Exception('MySQL charset setting error:<br/><code>'.$this->mysql->error.'</code>',EXCEPTION_MYSQL);
}
function query($query,$resultmode = MYSQLI_STORE_RESULT){
	$res = $this->mysql->query($query,$resultmode);
	if(!$res) throw new Exception(mysql_error().'<br/><code>'.$query.'</code>',EXCEPTION_MYSQL);
	return $res;
}
function table($name){
	return $this->getTableName($name);
}
function getTableName($name){
	if($this->getPrefix() && substr($name,0,strlen($this->getPrefix())) != $this->getPrefix())
		$name = $this->getPrefix().$name;
	return $name;
}
function getTables(){
	if($res = $this->query('SHOW TABLES'))
		return $res->fetch_all();
}
function getFieldType($name,$res){
	while($finfo = $res->fetch_field())
		if($finfo->name == $name)
			return $finfo->type;
}
function getNextId($table){
	if($table
		&& ($rs = $this->query("SHOW TABLE STATUS FROM `".$this->getDb()."` LIKE '".$this->getTableName($table)."'"))
		&& ($row = $rs->fetch_assoc())
	){
		return $row['Auto_increment'];
	}
}
function hasTable($name){
	$name = $this->getTableName($name);
	if($ar = $this->getTables()){
		foreach($ar as $row){
			if($row[0] == $name) return true;
		}
	}
}
function insert($table,$values){
	$query='insert into `'.$this->getTableName($table).'` (`'.implode('`,`',array_keys($values)).'`) values ('.implode(',',$values).')';
	return $this->query($query);
}
function update($table,$values,$cond){
	$ar=array();
	foreach($values as $name => $value) $ar[]='`'.$name.'`='.$value;
	$query = 'update `'.$this->getTableName($table).'` set '.implode(',',$ar).($cond ? ' where '.$cond : null);
	return $this->query($query);
}
function deleteRow($table,$cond){
	$query = 'DELETE FROM `'.$this->getTableName($table).'`'.($cond ? ' WHERE '.$cond : null);
	return $this->query($query);
}
function getInsertId(){
	return $this->mysql->insert_id;
}
function affectedRows(){
	return $this->mysql->affected_rows;
}
function str($v){
	return "'".$this->mysql->real_escape_string($v)."'";
}
function num($v){
	$v = str_replace(array(' ',','),array('','.'),$v);
	return strstr($v,'.') ? floatval($v) : intval($v);
}
static function numRows($rs){
	return $rs->num_rows;
}
static function fetch($rs){
	return $rs->fetch_assoc();
}
static function fetchArray($rs){
	return $rs->fetch_array(MYSQLI_BOTH);
}
}