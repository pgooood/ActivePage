<?php
class formFieldGallery{
private $ff;
private $formats;
private $sortOrder;
private $values;
private $table;
function __construct(formField $ff,$values = null){
	$this->ff = $ff;
	$this->setValues($values);
	if($this->ff->getRootElement()->hasAttribute('_ffg')){//Нельзя создавать больше одного объекта для одного поля
		throw new Exception('Duplicate formFieldGallery object for "'.$this->ff->getName().'" field');
	}
	$this->formats = array();
	$xml = $ff->getXML();
	$res = $xml->query('param',$ff->getRootElement());
	foreach($res as $param) $this->formats[] = $param->parentNode->removeChild($param);
	$this->ff->getRootElement()->setAttribute('_ffg','_ffg');
	$this->seTable('articles_images');
}
function seTable($v){$this->table = $v;}
function getTable(){return $this->table;}
function setValues($v){
	$this->values = $v;
	$this->sortOrder=isset($v[$this->ff->getName().'_sort_order']) ? explode(',',$v[$this->ff->getName().'_sort_order']) : array();
}
function fieldName($id){
	return $this->ff->getName().'_IMAGE_ID_'.$id;
}
function imageId($fieldName){
	$fieldName = substr($fieldName,strlen($this->ff->getName()));
	if(preg_match('/IMAGE_ID_([0-9]+)$/',$fieldName,$m))
		return intval($m[1]);
}
function deleteImages($id_article){
	$mysql = new mysql();
	if(
		($rs = $mysql->query('select `id` from `'.$mysql->getTableName($this->getTable()).'` where `id_article`='.$id_article.' and `field_name`="'.addslashes($this->ff->getName()).'"'))
	)while($r = $mysql->fetch($rs)){
		foreach($this->formats as $param){
			$e = $this->ff->getRootElement()->appendChild($param->cloneNode(true));
			$e->setAttribute('name',$fieldName);
			$e->setAttribute('uri',str_replace('%IMG_ID%',$r['id'],$e->getAttribute('uri')));
		}
	}
	$this->ff->removeImageFiles();
	$mysql->query('delete from `'.$mysql->getTableName($this->getTable()).'` where `id_article`='.$id_article.' and `field_name`="'.addslashes($this->ff->getName()).'"');
}
function prepareEdit($id_article){
	$this->setValues(null);
	$this->load($id_article);
}
function prepareUpdate($id_article,$values){
	$this->setValues($values);
	//vdump($this->getSubmitImages());
	$this->load($id_article);
	$this->addNew($id_article);
	return $this->values;
}
function getSubmitImages(){
	if(is_array($this->values)){
		$out = array('exist' => array(),'new' => array());
		if(isset($this->values[$this->ff->getName()])
			&& is_array($arValues = $this->values[$this->ff->getName()])
		){
			$offset = strlen($this->ff->getName().'_IMAGE_ID_');
			$titleFieldName = 'title_'.$this->ff->getName();
			$isTitle = isset($this->values[$titleFieldName]) && is_array($arTitles = $this->values[$titleFieldName]);
			$i = 0;
			foreach($arValues as $name => $path){
				$i++;
				if($id = intval(substr($name,$offset))){
					$out['exist'][$id] = array('fieldName' => $name
						,'path' => $path
						,'title' => $isTitle ? @$arTitles[$name] : null
						,'sort' => $i);
						,'ext' => pathinfo($path,PATHINFO_EXTENSION)
				}
			}
		}
		if(isset($this->values[$fieldName = $this->ff->getName().'___new']) && is_array($values = $this->values[$fieldName])){
			$sortOrder = array();
			foreach($this->sortOrder as $i => $str) if(preg_match('/new[0-9]+/',$str)) $sortOrder[] = $i+1;
			$fieldNameTitle = 'title_'.$fieldName;
			$isTitle = isset($this->values[$fieldNameTitle]) && is_array($arTitles = $this->values[$fieldNameTitle]);
			$i = 0;
			foreach($values as $src){
				if(file_exists($path = $_SERVER['DOCUMENT_ROOT'].$src)){
					$out['new'][] = array('fieldName' => $name
							,'path' => $path
							,'src' => $src
							,'title' => $isTitle ? @$arTitles[$i] : null
							,'sort' => isset($sortOrder[$i]) ? $sortOrder[$i] : '0');
					$i++;
				}
			}
		}
		return $out;
	}
}
private function load($id_article){
	$mysql = new mysql();
	$values = $this->getSubmitImages();
	$isUpdate = is_array($values);
	$arId = $isUpdate ? array_keys($values['exist']) : array();
	if(($rs = $mysql->query('select `id`,`title` from `'.$mysql->getTableName($this->getTable()).'` where `id_article`='.$id_article.' and `field_name`="'.addslashes($this->ff->getName()).'"'
			.(count($arId) ? ' and `id` not in('.implode(',',$arId).')' : null).' order by `sort`')
		)
		&& $mysql->numRows($rs)
	){
		$rowsToDelete = array();
		while($r = $mysql->fetch($rs)){
			$fieldName = $this->fieldName($r['id']);
			if($isUpdate){
				$this->values[$fieldName] = jpgScheme::VALUE_DELETE;
				$rowsToDelete[] = $r['id'];
			}
			foreach($this->formats as $param){
				if(!$isUpdate && !$param->hasAttribute('preview')) continue;
				$e = $this->ff->getRootElement()->appendChild($param->cloneNode(true));
				$e->setAttribute('name',$fieldName);
				$e->setAttribute('uri',str_replace('%IMG_ID%',$r['id'],$e->getAttribute('uri')));
				if($r['title']) $e->setAttribute('title',$r['title']);
			}
		}
		if($isUpdate && count($rowsToDelete))
			$mysql->query('delete from `'.$mysql->getTableName($this->getTable()).'` where id in('.implode(',',$rowsToDelete).')');
	}
	if($isUpdate){
		$v = array();
		foreach($this->sortOrder as $i => $str) if(preg_match('/id([0-9]+)/',$str,$m)){
			$v[$m[1]] = '('.$m[1].',"'.addslashes($values['exist'][$m[1]]['title']).'",'.($i+1).')';
		}
		//vdump($values);
		if(count($v)){
			$mysql->query('insert into `'.$mysql->getTableName($this->getTable()).'` (`id`,`title`,`sort`) values '.implode(',',$v).' on duplicate key update `title`=values(`title`),`sort`=values(`sort`)');
		}
	}
}
private function addNew($id_article){
	if(!is_array($values = $this->getSubmitImages())) return;
	$mysql = new mysql();
	foreach($values['new'] as $img){
		if($mysql->insert($this->getTable(),array(
			'field_name'=>'"'.$this->ff->getName().'"'
			,'id_article'=>$id_article
			,'title'=>$img['title'] ? '"'.addslashes($img['title']).'"' : 'null'
			,'sort'=>$img['sort']
			,'ext'=>$img['ext'] ? '"'.addslashes($img['ext']).'"' : 'null'
		))){
			$img_id = $mysql->getInsertId();
			$name = $this->fieldName($img_id);
			$this->values[$name] = $img['src'];
			foreach($this->formats as $param){
				$e = $this->ff->getRootElement()->appendChild($param->cloneNode(true));
				$e->setAttribute('name',$name);
				$e->setAttribute('uri',str_replace('%IMG_ID%',$img_id,$e->getAttribute('uri')));
			}
		}
	}
}
}
?>
