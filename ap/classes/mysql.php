<?
class mysql{
private $conn;
private $id;
function __construct($id = null){
	global $_site;
	$this->id = $id ? $id : 'default';
	if($e = $_site->query('/site/mysql/con'.($id ? '[@id="'.$id.'"]' : ''))->item(0)){
		$this->db = $e->getAttribute('db');
		$this->host = $e->getAttribute('host');
		$this->user = $e->getAttribute('user');
		$this->pass = $e->getAttribute('pass');
		$this->prefix = trim($e->getAttribute('pref'));
		$this->charset =  $e->hasAttribute('charset') ? $e->getAttribute('charset') : 'utf8';
		$this->connect();
	}else throw new Exception('MySQL connection not found',EXCEPTION_MYSQL);
}
function connect(){
	if(!session_id()) session_start();
	if(isset($_SESSION['ap_mysql_con_'.$this->id]) && $_SESSION['ap_mysql_con_'.$this->id]){
		$this->conn = $_SESSION['ap_mysql_con_'.$this->id];
	}else{
		$_SESSION['ap_mysql_con_'.$this->id] =
		$this->conn = mysql_connect($this->host,$this->user,$this->pass);
		if(!$this->conn) throw new Exception(mysql_error(),EXCEPTION_MYSQL);
		$this->query('set names '.$this->charset);
		if(!mysql_select_db($this->db,$this->conn))
			throw new Exception('No database',EXCEPTION_MYSQL);
	}
}
function query($query,$fetch = false){
	$res = @mysql_query($query,$this->conn);
	if($res===false) throw new Exception(mysql_error().'<br/><code>'.$query.'</code>',EXCEPTION_MYSQL);
	if($fetch) return mysql_fetch_assoc ($res);
	else return $res;
}
function getPrefix(){
	return $this->prefix;
}
function getTableName($name){
	if($this->getPrefix() && substr($name,0,strlen($this->getPrefix())) != $this->getPrefix())
		$name = $this->getPrefix().$name;
	return $name;
}
function getTables(){
	$res = @mysql_list_tables($this->db,$this->conn);
	if(!$res) throw new Exception(mysql_error(),EXCEPTION_MYSQL);
	return $res;
}
function getFieldType($name,$res){
	$len = mysql_num_fields($res);
	for($i=0; $i<$len; $i++){
		if(($meta = mysql_fetch_field($res,$i))
			&& $meta->name == $name
		) return $meta->type;
	}
}
function getNextId($table){
	if($table
		&& ($rs = $this->query("SHOW TABLE STATUS FROM `".$this->db."` LIKE '".$this->getTableName($table)."'"))
		&& ($row = mysql_fetch_assoc($rs))
	){
		return $row['Auto_increment'];
	}
}
function hasTable($name){
	$name = $this->getTableName($name);
	if($rs = $this->getTables()){
		while($row = mysql_fetch_row($rs)){
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
	return mysql_insert_id();
}
function affectedRows(){
	return mysql_affected_rows($this->conn);
}
static function fetch($rs){
	return mysql_fetch_assoc($rs);
}
static function num_rows($rs){
	return mysql_num_rows($rs);
}
static function str($v){
	return '"'.addslashes($v).'"';
}
static function num($v){
	$v = str_replace(array(' ',','),array('','.'),$v);
	return strstr($v,'.') ? floatval($v) : intval($v);
}
}