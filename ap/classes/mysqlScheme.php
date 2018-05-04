<?php
class mysqlScheme{
private $values = array();
function add($uri,$value){
	if(($url = parse_url($uri))
		&& isset($url['host'])
		&& isset($url['path'])
		&& isset($url['fragment'])
	){
		$url['path'] = trim($url['path'],'/');
		$query = isset($url['query']) ? $url['query'] : null;
		if(false!==($v = $this->isNewRow($query)))
			$this->values[$url['host']][$url['path']]['__new__'][$v][$url['fragment']] = $value;
		elseif($query)
			$this->values[$url['host']][$url['path']][$this->parseQuery($query)][$url['fragment']] = $value;
	}
}
function getFieldType($table,$field,$mysql = null){
	if(!is_object($mysql) || !($mysql instanceof mysql))
		$mysql = new mysql();
	if(!isset($this->cacheTableCols))
		$this->cacheTableCols = array();
	if(!isset($this->cacheTableCols[$table]))
		$this->cacheTableCols[$table] = $mysql->query('select * from `'.$mysql->getTableName($table).'` limit 0,1');
	if($this->cacheTableCols[$table])
		return $mysql->getFieldType($field,$this->cacheTableCols[$table]);
}
function prepValue($type,$value){
	switch($type){
		case 'int':
		case 'real':
			if(!is_numeric($value))
				$value = 'NULL';
			break;
		case 'string':
		case 'blob':
		default:
			$value = '"'.addslashes($value).'"';
			break;
	}
	return $value;
}
function save(){
	foreach($this->values as $con => $tables){
		$mysql = new mysql($con);
		foreach($tables as $table => $conditions){
			foreach($conditions as $condition => $columns){
				if(!$condition) continue;
				if($condition == '__new__'){
					foreach($columns as $cols){
						$row = array();
						foreach($cols as $name => $value)
							$row['`'.$name.'`'] = $this->prepValue($this->getFieldType($table,$name,$mysql),$value);
						$query = 'INSERT INTO `'.$mysql->getTableName($table).'` ('.implode(',',array_keys($row)).') VALUES ('.implode(',',$row).')';
						$mysql->query($query);
					}
				}else{
					$arCond = explode(' AND ',$condition);
					if(preg_match('/^`([^`]+)`="(.*)"$/',$arCond[0],$m)){
						$row = array();
						foreach($arCond as $cond){
							if(preg_match('/^`([^`]+)`="(.*)"$/',$cond,$m)){
								$row['`'.$m[1].'`'] = $this->prepValue($this->getFieldType($table,$name,$mysql),$m[2]);
							}
						}
						foreach($columns as $name => $value)
							$row['`'.$name.'`'] = $this->prepValue($this->getFieldType($table,$name,$mysql),$value);
						$row2 = array();
						foreach($columns as $name => $value)
							$row2[] = '`'.$name.'`=VALUES(`'.$name.'`)';
						$query = 'INSERT INTO `'.$mysql->getTableName($table).'` ('.implode(',',array_keys($row)).') VALUES ('.implode(',',$row).')'
							.' ON DUPLICATE KEY UPDATE '.implode(',',$row2);
						//vdump($query);
						$mysql->query($query);
					}else{
						$row = array();
						foreach($columns as $name => $value){
							$row[] = '`'.$name.'`='.$this->prepValue($this->getFieldType($table,$name,$mysql),$value);
						}
						$query = 'UPDATE `'.$mysql->getTableName($table).'` SET '.implode(',',$row).' WHERE '.$condition;
						$mysql->query($query);
					}
				}
			}
		}
	}
}
static function getFieldName($uri){
	return  parse_url($uri,PHP_URL_FRAGMENT);
}
function get($uri){
	if(($url = parse_url($uri))
		&& isset($url['host'])
		&& isset($url['path'])
		&& isset($url['fragment'])
		&& isset($url['query'])
		&& ($cond = $this->parseQuery($url['query']))
	){
		$url['path'] = trim($url['path'],'/');
		if(($row = $this->getRow($url['host'],$url['path'],$cond))
			&& isset($row[$url['fragment']])
		){
			return $row[$url['fragment']];
		}
	}
}
function getRow($con,$table,$cond){
	if(isset($cache[$con][$table][$cond]))
		return $cache[$con][$table][$cond];
	if($table && $cond){
		$mysql = new mysql($con);
		$query = 'SELECT * FROM `'.$mysql->getTableName($table).'` WHERE '.$cond;
		if($rs = $mysql->query($query)){
			return $cache[$con][$table][$cond] = $mysql->fetch($rs);
		}
	}
}
static function isNewRow($val){
	if($val){
		if(preg_match('/__new__=([^&]+)/',$val,$m))
			return $m[1];
	}else return 1;
	return false;
}
static function parseQuery($val){
	if($val){
		$tmp1 = explode('&',$val);
		$tmp2 = array();
		foreach($tmp1 as $pair){
			$tmp3 = explode('=',$pair);
			$tmp2[] = '`'.$tmp3[0].'`="'.addslashes($tmp3[1]).'"';
		}
		return implode(' AND ',$tmp2);
	}
}
}