<?php
class mysqlToXml extends mysqlTable{
protected $pageParamName
	,$skipEmptyFields
	,$pagingUrl
	,$images = array()
	,$fieldFloat = array()
	,$fieldCond = array()
	,$fieldDate = array()
	,$currentSection
	,$sort_control = false;
function __construct($table){
	parent::__construct($table);
	$this->image = null;
	$this->setIdField('id');
	$this->setPageParamName('x-x-x');
	$this->setRowSize(null);
	$this->setPageSize(10);
	$this->setQueryFields();
	$this->setAttrFields(array('id'));
	$this->skipEmptyFields = true;
}
function setCurrentSection($val){$this->currentSection = $val;}
function getCurrentSection(){return $this->currentSection;}
function setPageParamName($val){$this->pageParamName = $val;}
function setRowSize($num){$this->listRowSize = $num;}
function setGroupingByField($fieldName){$this->groupingField = $fieldName;}
function setPageSize($value){$this->pageSize = $value;}
function getPageSize(){return $this->pageSize;}
function setQueryFields($arr = null){$this->queryFields = $arr;}
function setAttrFields($arr = null){$this->attrFields = $arr;}
function setIdField($val){$this->id_field = $val;}
function addDateFormat($fieldName,$formatStr){
	$this->fieldDate[$fieldName] = $formatStr;
}
function addFloatFormat($fieldName,$decimals = 2,$dec_point = ',',$thousands_sep = ' '){
	$this->fieldFloat[$fieldName] = array('decimals'=>$decimals,'dec_point'=>$dec_point,'thousands_sep'=>$thousands_sep);
}
function addImg($params){
	$this->images[] = $params;
}
function setFieldCond($name,$cond){
	$this->fieldCond[$name] = $cond;
}
function __call($name,$params){
	switch($name){
		case 'addImage':
			if(count($params)==1 && is_array($params[0])){
				$this->addImg($params[0]);
			}elseif(!is_array($params[0])){
				$p = array(
					'width'			=>	$params[0],
					'height'		=>	$params[1],
					'path'			=>	$params[2],
					'prefix'		=>	$params[3],
					'fileFieldName'	=>	$params[4]
				);
				if(isset($params[5]))$p['altFieldName'] = $params[5];
				if(isset($params[6]))$p['postfix'] 		= $params[6];
				$this->addImg($p);
			}
		break;
	}
}
function addNl2Br($fieldName){
	$this->fieldNl2Br[$fieldName] = true;
}
function setPagingUrl($value){
	$this->pagingUrl = $value;
}
function listToXML($outTagname,$condition = null,$sort = null){
	$limit = $this->getLimit(param($this->pageParamName),$this->pageSize,$condition);
	$out = new xml(null,null,false);
	$outTag = $out->dd()->appendChild($out->createElement($outTagname,array(
		'rows'=>$limit['num_rows'],
		'pages'=>$limit['page_num'],
		'pagesize'=>$limit['page_size'],
		'page'=>$limit['page_current'],
		'pageParam'=>$this->pageParamName)));
	if($this->pagingUrl) $outTag->setAttribute('pagingUrl',$this->pagingUrl);
	if(isset($this->groupingField)){
		if($sort) $sort = $this->groupingField.','.$sort;
		else $sort = $this->groupingField;
	}
	$this->getRow($row_set,$condition,$limit['limit_string'],$sort);
	$root = $outTag;
	$counter = 0;
	while($row = $this->fetch($row_set)){
		//группируем записи, если надо
		if(isset($this->groupingField)){
			if(!(isset($row[$this->groupingField]))) $root = $outTag;
			elseif($root->getAttribute('title')!=$row[$this->groupingField])
				$root = $outTag->appendChild($out->createElement('group',array('title'=>$row[$this->groupingField])));
		}elseif($this->listRowSize && !($counter++%$this->listRowSize)){
			$root = $outTag->appendChild($out->createElement('tr'));
		}
		//делаем запись
		$rec = $root->appendChild($out->createElement('row'));
		$this->addValuesToRecord($rec,$row,$out);
	}
	return $out;
}
function rowToXML($outTagname,$condition,&$row){
	$this->getRow($row_set,$condition);
	if($row = $this->fetch($row_set)){
		$page = 1;
		if($this->sort_control){
			$query = 'active=1 and `sort`<='.$row['sort'];
			if($this->section_control){
				$query.= ' and `'.$this->section_field.'`="'.$this->getCurrentSection().'"';
			}
			$page = ceil($this->getNumRows($query)/$this->getPageSize());
		}elseif($this->pageParamName)
			$page = intval(param($this->pageParamName)) ? intval(param($this->pageParamName)) : 1;
		$out = new xml('new_doc');
		$rec = $out->dd()->appendChild($out->createElement($outTagname,array('page'=>$page)));
		$this->addValuesToRecord($rec,$row,$out);
		return $out;
	}
	return null;
}
protected function addValuesToRecord(&$rec,&$row,&$out){
	$fields = $this->queryFields;
	//добавляем атрибуты в запись, если заданы
	if(is_array($this->attrFields)) foreach($this->attrFields as $fieldName){
		$v = $row[$fieldName];
		if($this->skipEmptyFields && !$v) continue;
		if(isset($this->fieldFloat[$fieldName]))
			$v = number_format($v,$this->fieldFloat[$fieldName]['decimals'],$this->fieldFloat[$fieldName]['dec_point'],$this->fieldFloat[$fieldName]['thousands_sep']);
		if(isset($this->fieldDate[$fieldName])){
			$time = strtotime($v);
			if($this->fieldDate[$fieldName]=='russian')
				$v = date('d '.$this->getRussianMonth($time).' Y',$time);
			else $v = date($this->fieldDate[$fieldName],$time);
		}
		$rec->setAttribute($fieldName,$v);
	}
	//берем все поля записи, если не заданы
	if(!is_array($fields)){
		$fields = array_keys($row);
		if(is_array($this->attrFields)) $fields = array_diff($fields,$this->attrFields);
	}
	//создаем поля в записи
	foreach($fields as $fieldName){
		$v = $row[$fieldName];
		if($this->skipEmptyFields && !$v) continue;
		if(isset($this->fieldNl2Br[$fieldName]))
			$v = nl2br($v);
		if(isset($this->fieldFloat[$fieldName]))
			$v = number_format($row[$fieldName],$this->fieldFloat[$fieldName]['decimals'],$this->fieldFloat[$fieldName]['dec_point'],$this->fieldFloat[$fieldName]['thousands_sep']);
		if($isDate = isset($this->fieldDate[$fieldName])){
			$time = strtotime($v);
			if($this->fieldDate[$fieldName]=='russian')
				$v = date('j '.$this->getRussianMonth($time).' Y',$time);
			else $v = date($this->fieldDate[$fieldName],$time);
		}
		$e = $rec->appendChild($out->createElement($fieldName,null,$v));
		if($isDate) $e->setAttribute('value',date('Y-m-d\TH:i:s\Z',$time));
		
	}
	//добавляем картинки
	foreach($this->images as $key => $image){
		$file_name = null;
		if(is_array($image['fileFieldName'])){
			$ar = array();
			foreach($image['fileFieldName'] as $fname) $ar[] = $row[$fname];
			$file_name = implode('_',$ar);
		}else $file_name = $row[$image['fileFieldName']];
		$filepath = $image['path'].$image['prefix'].$file_name.$image['postfix'].'.jpg';
		if(is_file($filepath)){
			list($width, $height) = getimagesize($filepath);
			if($image['width'] && !$image['height']){
				$image['height'] = ceil(($image['width'] * $height)/$width);
			}elseif(!$image['width'] && $image['height']){
				$image['width'] = ceil(($image['height'] * $width)/$height);
			}
			if(!$image['width'] && !$image['height']){
				$image['width'] = $width;
				$image['height'] = $height;
			}
			$params = array();
			if(isset($image['name'])){
				$params['name'] = $image['name'];
			}else{
				$params['name'] = $key;
			}
			$params['src'] 		= $filepath;
			$params['alt']		= $image['altFieldName'] ? $row[$image['altFieldName']] : '';
			$params['width']	= $image['width'];
			$params['height']	= $image['height'];
			$rec->appendChild($out->createElement('img',$params));
		}
	}
}
static function getRussianMonth($time){
	switch(date('m',$time)){
		case 0: return 'января';
		case 1: return 'февраля';
		case 2: return 'марта';
		case 3: return 'апреля';
		case 4: return 'мая';
		case 5: return 'июня';
		case 6: return 'июля';
		case 7: return 'августа';
		case 8: return 'сентября';
		case 9: return 'октября';
		case 10: return 'ноября';
		case 11: return 'декабря';
	}
}
}