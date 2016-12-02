<?
class mysqllist extends rowlist{
private $params;
private $fieldFloat = array();
private $fieldDate = array();
private $fieldNl2Br = array();
function __construct(DOMElement $rowlist,$query_params){
	$this->setQueryParams($query_params);
	parent::__construct($rowlist,$this->numRows(),$this->params['page']);
}
function setQueryParams($params,$reset = false){
	if(is_array($params)){
		if($reset) $this->params = $params;
		else{
			if(!is_array($this->params)) $this->params = array();
			$this->params = array_merge($this->params,$params);
		}
	}
	return $this->params;
}
static function getParams($params){
	$defparams = array(
		'idcol' => 'id',
		'activecol' => 'active',
		'sortcol' => 'sort',
		'sortdirect' => 'asc',
		'sortcontrol' => true,
		'con' => 'default',
		'cols' => '*',
		'table' => null,
		'alias' => null,
		'join' => null,
		'cond' => null,
		'group' => null,
		'limit' => null,
		'order' => null,
		'page' => 1
	);
	return is_array($params) ? array_merge($defparams,$params) : $defparams;
}
static function select($params){
	$params = mysqllist::getParams($params);
	if(!is_array($params)
		|| !$params['con']
		|| !$params['cols']
		|| !$params['table']
	) throw new Exception('mysqllist params error',EXCEPTION_MYSQL);
	
	$mysql = new mysql($params['con']);
	$params['table'] = $mysql->getTableName($params['table']);
	extract($params);
	$query = "SELECT $cols FROM `$table`"
		.($alias ? " AS `$alias`" : null)
		.($join ? $join : null)
		.($cond ? " WHERE $cond" : null)
		.($group ? " GROUP BY $group" : null)
		.($order ? " ORDER BY $order" : null)
		.($limit ? " LIMIT $limit" : null);
	return $mysql->query($query);
}
function importSettings(DOMElement $e){
	$res = parent::importSettings($e);
	if($e->hasAttribute($name = 'sort')){
		$this->params['sortdirect'] = $e->getAttribute($name);
	}
	return $res;
}
function numRows(){
	$params = array_merge($this->params,array(
		'cols' => 'COUNT(*) AS `num_rows`',
		'limit' => null,
		'sort' => null,
	));
	if($rs = $this->select($params)){
		$num_rows = mysql_num_rows($rs);
		if($num_rows==1){
			$row = mysql_fetch_assoc($rs);
			return $row['num_rows'];
		}
		return $num_rows;
	}
}
function build(){
	$params = $this->getParams($this->params);
	$params['limit'] = $this->getStartIndex().','.$this->getPageSize();
	if(!$params['order'] && $params['sortcol']){
		$params['order'] = '`'.$params['sortcol'].'`'.($params['sortdirect'] ? ' '.$params['sortdirect'] : null);
	}
	if($rs = $this->select($params)){
		$headers = $this->getHeaders();
		$values = array();
		$counter = $this->getStartIndex();
		while($row = mysql_fetch_assoc($rs)){
			$counter++;
			foreach($headers as $h){
				if(isset($this->fieldDate[$h]))
					$row[$h] = date($this->fieldDate[$h],strtotime($row[$h]));
				if(isset($this->fieldFloat[$h]))
					$row[$h] = number_format($row[$h],$this->fieldFloat[$h]['decimals'],$this->fieldFloat[$h]['dec_point'],$this->fieldFloat[$h]['thousands_sep']);
				if(isset($this->fieldNl2Br[$h]))
					$row[$h] = nl2br($row[$h]);
				switch($h){
					case 'sort':
						$values[$h] = $counter;
						break;
					case 'active':
						$values[$h] = isset($row[$params['activecol']]) ? !$row[$params['activecol']] : null;
						break;
					default:
						$values[$h] = isset($row[$h]) ? $row[$h] : null;
				}
			}
			$this->addRow($row[$params['idcol']],$values);
		}
	}
}
function deleteRow($id){
	$params = mysqllist::getParams($this->params);
	if(!is_array($params)
		|| !$params['con']
		|| !$params['table']
		|| !$params['idcol']
		|| !$params['sortcol']
	) throw new Exception('mysqllist->deleteRow params error',EXCEPTION_MYSQL);
		
	$mysql = new mysql($params['con']);
	$params['table'] = $mysql->getTableName($params['table']);
	extract($params);
	
	//проверяем айди для удаления
	if($id && !is_array($id)) $id = array($id);
	foreach($id as $i => $v)
		if(!($id[$i] = intval($v))) unset($id[$i]);
	if(!count($id)) return;
	if($mysql->query("delete from `$table` where `$idcol` in (".implode(',',$id).")")){ //удаляем
		if($sortcontrol
			&& ($row_set = $mysql->query("select * from `$table`".($alias ? ' as `'.$alias.'`' : null).($cond ? ' where '.$cond : null)." order by `$sortcol`"))
			&& $mysql->getFieldType($sortcol,$row_set)=='int'
		){ //восстанавливаем сортировку
			$ar = array();
			$counter = 1;
			while($row = mysql_fetch_array($row_set)){
				if($row[$sortcol] != $counter) $ar[] = '('.$row[$idcol].','.$counter.')';
				$counter++;
			}
			if(count($ar))
				return $mysql->query("INSERT IGNORE INTO `$table` (`$idcol`,`$sortcol`) VALUES ".implode(',',$ar)." ON DUPLICATE KEY UPDATE `$sortcol`=VALUES(`$sortcol`)");
		}
		return true;
	}
}
function moveRow($id,$pos){
	$pos = intval($pos);
	$params = mysqllist::getParams($this->params);
	if(!is_array($params)
		|| !$params['con']
		|| !$params['table']
		|| !$params['idcol']
		|| !$params['sortcol']
	) throw new Exception('mysqllist->moveRow params error',EXCEPTION_MYSQL);
	
	$mysql = new mysql($params['con']);
	$params['table'] = $mysql->getTableName($params['table']);
	extract($params);
	//получаем количество записей
	if(!($rs = $mysql->query("select count(*) as `numRows` from `$table`".($alias ? ' as `'.$alias.'`' : null).($cond ? " where $cond" : null)))
		|| !($row = mysql_fetch_array($rs))
		|| !($num_rows = $row['numRows'])
	) return;
	//проверяем корректность новой позиции
	if($pos<1) return;
	elseif($pos > $num_rows) $pos = $num_rows;
	//получаем запись позицию которой нужно изменить
	if(!($rs = $mysql->query("select * from `$table` where `$idcol`='$id'"))
		|| !($row = mysql_fetch_array($rs))
	) return;
	$source_sort = $row[$sortcol];
	//ищем запись с заданным порядковым номером
	if(!($rs = $mysql->query("select * from `$table`".($alias ? ' as `'.$alias.'`' : null).($cond ? " where $cond" : null)." order by `$sortcol` $sortdirect limit ".($pos-1).",1"))
		|| !($row = mysql_fetch_array($rs))
	) return;
	$target_sort = $row[$sortcol];
	//Корректируем значения поля sort
	if(!$mysql->query("update `$table`".($alias ? ' as `'.$alias.'`' : null)." set `$sortcol`=`$sortcol` - 1 where".($cond ? ' '.$cond.' and' : null)." `$sortcol` > $source_sort")) return;
	if(!$mysql->query("update `$table`".($alias ? ' as `'.$alias.'`' : null)." set `$sortcol`=`$sortcol` + 1 where".($cond ? ' '.$cond.' and' : null)." `$sortcol` >= $target_sort")) return;
	//задаем нужную позицию заданной записи
	if(!$mysql->query("update `$table` SET `$sortcol`=$target_sort where `$idcol`='$id'")) return;
	return true;
}
function addDateFormat($fieldName,$formatStr){
	$this->fieldDate[$fieldName] = $formatStr;
}
function addFloatFormat($fieldName,$decimals = 2,$dec_point = ',',$thousands_sep = ' '){
	$this->fieldFloat[$fieldName] = array('decimals'=>$decimals,'dec_point'=>$dec_point,'thousands_sep'=>$thousands_sep);
}
function addNl2Br($fieldName){
	$this->fieldNl2Br[$fieldName] = true;
}
function addBoolField($fieldName){
	$this->fieldBool[$fieldName] = true;
}
function getTableName(){
	$params = mysqllist::getParams($this->params);
	$mysql = new mysql($params['con']);
	return $mysql->getTableName($params['table']);
}
}