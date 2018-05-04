<?php
class mysqlTable extends mysql{
protected $customFieldList = '*',$joins,$tableAlias,$groupby;
function __construct($table){
	parent::__construct();
	$this->setTable($table);
}
function getTable(){return $this->table;}
function setTable($val){$this->table = $this->getTableName($val);}
function setJoins($v,$alias = null,$groupby = null){
	$this->joins = $v;
	$this->tableAlias = $alias;
	$this->groupby = $groupby;
}
function setCustomFieldList($fields,$clean = true){
	$this->customFieldList = (!$clean && $this->getCustomFieldList() ? $this->getCustomFieldList().',' : '').$fields;
}
function getCustomFieldList(){return $this->customFieldList;}
function getAlias(){return $this->tableAlias;}
function getRow(&$row_set,$condition=null,$limit=null,$sort=null){
	$query='select '.$this->customFieldList.' from `'.$this->table.'`'
		.($this->tableAlias ? ' as `'.$this->tableAlias.'`' : null)
		.($this->joins ? ' '.$this->joins : null)
		.($condition ? ' where '.$condition : null)
		.($this->groupby ? ' GROUP BY '.$this->groupby : null)
		.($sort ? ' order by '.$sort : null)
		.($limit ? ' limit '.$limit : null);
	if(!($row_set=$this->query($query)))return false;
	return true;
}
function getNumRows($condition=null){
	$num = 0;
	$query='select count(*) as num_rows from `'.$this->getTable().'`'
		.($this->tableAlias ? ' as `'.$this->tableAlias.'`' : null)
		.($this->joins ? ' '.$this->joins : null)
		.($condition ? ' where '.$condition : null)
		.($this->groupby ? ' GROUP BY '.$this->groupby : null);
	if($rs=$this->query($query)){
		if($this->joins) $num = $this->numRows($rs);
		elseif($row = $this->fetch($rs)) $num = $row['num_rows'];
	}
	return $num;
}	
function getLimit($page_current,$page_size,$condition=null){
	$num_rows = $this->getNumRows($condition);
	$page_num = $num_rows ? ceil($num_rows/$page_size) : 1;
	if(intval($page_current) < 1) $page_current = 1;
	elseif(intval($page_current) > $page_num) $page_current = $page_num;
	$limit_start = ($page_current-1)*$page_size;
	return $limit = array(
		'num_rows'=>$num_rows,
		'page_num'=>$page_num,
		'page_size'=>$page_size,
		'page_current'=>$page_current,
		'limit_start'=>$limit_start,
		'limit_string'=>$limit_start.','.$page_size
	);
}
function insert($values){
	return parent::insert($this->getTable(),$values);
}
function update($values,$cond){
	return parent::update($this->getTable(),$values,$cond);
}
function delete($cond){
	return parent::delete($this->getTable(),$cond);
}
}