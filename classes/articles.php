<?php
class articles extends module{
function run(){
	global $_out;
	if($row = self::getRequestRow()){
		if(($xml = $this->getDetailXML($this->getListProp('tagNameText'),$row,$val))){
				if(($e = $_out->xmlIncludeTo($xml,'/page/section'))
					&& ($title = $_out->evaluate('string(title)',$e))
				) $this->setDetailMetaTags($val);
		}else{
			throw new Exception(null,EXCEPTION_404);
		}
	}elseif($xml = $this->getListXML($this->getListProp('tagNameList'))){
		$_out->xmlIncludeTo($xml,'/page/section');
		$this->setListMetaTitle($this->getSection()->getTitle());
	}
}
static function getRequestRow(){
	if($v = intval(param('row')))
		return $v;
}
function getTable($smart_content = true){
	$tb = new mysqlToXml('articles');
	$tb->addDateFormat('date','d.m.Y');
	$tb->setCustomFieldList($this->f('id').'
,'.$this->f('section').'
,'.$this->f('module').'
,'.$this->f('date').'
,'.$this->f('title').'
,'.$this->f('announce').'
,'.($smart_content
	? 'IF(ISNULL('.$this->f('article').') OR '.$this->f('article').'="",'.$this->f('announce').','.$this->f('article').') AS `article`'
	: $this->f('article')).'
,'.$this->f('active').'
,'.$this->f('sort'));
	return $tb;
}
function getTableAlias(){
	return null;
}
function f($name){
	$alias = $this->getTableAlias();
	return '`'.($alias ? $alias.'`.`' : null).$name.'`';
}

/**
* Список статей
*/
function getListProp($name){
	$v = $this->evaluate('string(list/@'.$name.')');
	switch($name){
		case 'tagNameText': if(!$v) $v = 'articlesRow'; break;
		case 'tagNameList': if(!$v) $v = 'articles'; break;
		case 'imageLimit': if(!$v)  $v = 1; break;
		case 'imageExt': if(!$v)  $v = 'jpg'; break;
	}
	return $v;
}
function setListProp($name,$value){
	if($name
		&& ($e = $this->query('list')->item(0))
	){
		if($value) $e->setAttribute($name,$value);
		elseif($e->hasAttribute($name)) $e->removeAttribute($name);
	}
}
function setListMetaTitle($v){
	global $_out;
	$_out->setMeta('title',$v);
}
function getListTable(){
	$tb = $this->getTable(false);
	$tb->setAttrFields(array('id','section'));
	$tb->setQueryFields(array('date','title','article'));
	
	if($v = $this->getListProp('nl2br'))
		$tb->addNl2Br('announce');
	if($v = $this->getListProp('coll'))
		$tb->setRowSize($v);
	if($v = $this->getListProp('pageParam'))
		$tb->setPageParamName($v);
	$tb->setPageSize($this->getListProp('pageSize'));
	
	$listQueryFields = array('date','title','announce');
	if($this->getListProp('includeContent'))
		$listQueryFields[] = 'article';
	$tb->setQueryFields($listQueryFields);
	
	if(!$tb->getPageSize()) $tb->setPageSize(10);
	return $tb;
}
function getListCondition(){
	return $this->f('active').'=1 and '
			.$this->f('section').'="'.$this->getSection()->getId().'" and '
			.$this->f('module').'="'.$this->getId().'"';
}
function getListXML($tagName){
	$v = $this->getListProp('sort');
	if($xml = $this->getListTable()->listToXML($tagName,$this->getListCondition(),'sort '.($v ? $v : 'asc'))){
		$id = array();
		$res = $xml->query('//row[@id]');
		foreach($res as $row) $id[] = $row->getAttribute('id');
		$img = $this->getImages($id,true,$this->getListProp('imageLimit'));
		foreach($res as $row) $this->setImages($row,$img);
		return $xml;
	}
}

/**
* Отдельная статья
*/
function setDetailMetaTags($row){
	global $_out;
	$_out->setMeta('title',$row['title']);
	if(($v = strip_tags($row['announce']))){
		if(mb_strlen($v) > 255) $v = mb_substr($v,0,255);
		$_out->setMeta('description',$v);
	}
}
function getDetailTable(){
	$tb = $this->getTable();
	$tb->setAttrFields(array('id','date'));
	$tb->setQueryFields(array('date','title','article'));
	return $tb;
}
function getDetailCondition($row){
	return $this->f('active').'=1 and '
			.$this->f('section').'="'.$this->getSection()->getId().'" and '
			.$this->f('module').'="'.$this->getId().'" and '
			.$this->f('id').'= "'.$row.'"';
}
function getDetailXML($tagName,$row,&$val){
	if($xml = $this->getDetailTable()->rowToXML($tagName,$this->getDetailCondition($row),$val)){
		$this->setImages($xml->de(),$this->getImages($val['id'],true));
		return $xml;
	}
}

/**
* Анонсы
*/
function getAnnounceTable($arParams){
	$tb = $this->getTable();
	$tb->setPageParamName('xxx');
	$tb->setPageSize(3);
	$tb->setAttrFields(array('id','section','date'));
	$listQueryFields = array('date','title','announce');
	if(!empty($arParams['includeContent']))
		$listQueryFields[] = 'article';
	$tb->setQueryFields($listQueryFields);
	return $tb;
}
function announce($tagname,$sort = null,$size = null,$parent = null,$arParams = null){
	global $_out;
	
	$tb = $this->getAnnounceTable($arParams);
	if($size) $tb->setPageSize($size);
	if(($xml = $tb->listToXML($tagname
				,$this->getListCondition()
				,$sort
			))
		&& (($parent && ($e = $_out->xmlIncludeTo($xml,$parent))) || ($e = $_out->xmlInclude($xml)))
	){
		$id = array();
		$imageLimit = isset($arParams['imageLimit']) ? intval($arParams['imageLimit']) : null;
		$res = $_out->query('.//row[@id]',$e);
		foreach($res as $row) $id[] = $row->getAttribute('id');
		$img = $this->getImages($id,true,$imageLimit);
		foreach($res as $row) $this->setImages($row,$img,!$imageLimit);
	}
}
function onPageReady($params = null){
	if(is_array($params) && isset($params['tagname']))
		$this->announce($params['tagname'],$params['sort'],$params['size'],$params['out_place']);
}

/**
* Картинки
*/
function getImages($id,$preview = false,$limit = null){
	global $_out;
	if(!is_array($id)) $id = array($id);
	if(!count($id)) return;
	$res = array();
	$mysql = new mysql();
	$counter = array();
	$ext = $this->getListProp('imageExt');
	if($rs = $mysql->query('SELECT img.*,art.section,art.module
FROM `'.$mysql->getTableName('articles_images').'` AS img
LEFT JOIN `'.$mysql->getTableName('articles').'` AS art ON art.id=img.id_article
WHERE `id_article` IN ('.implode(',',$id).') AND img.`active`=1
ORDER BY `id_article`,`sort`')){
		while($r = $mysql->fetch($rs)){
			if($limit > 0){
				if(!isset($counter[$r['id_article']])) $counter[$r['id_article']] = 0;
				if($counter[$r['id_article']] >= $limit) continue;
				$counter[$r['id_article']]++;
			}
			$v = array();
			if(is_file($path = 'userfiles/articles/'.$r['section'].'/'.$r['id'].'.'.$ext)){
				list($width, $height) = getimagesize($path);
				$v['img'] = $_out->createElement('img',array(
					'id' => $r['id'],
					'src' => $path,
					'width' => $width,
					'height' => $height
				));
				if($r['title']) $v['img']->setAttribute('alt',$r['title']);
			}

			if($preview && is_file($path = 'userfiles/articles/'.$r['section'].'/'.$r['id'].($preview ? '_preview' : null).'.'.$ext)){
				list($width, $height) = getimagesize($path);
				$v['prv'] = $_out->createElement('preview',array(
					'src' => $path,
					'width' => $width,
					'height' => $height
				));
				if($r['title']) $v['prv']->setAttribute('alt',$r['title']);
			}

			if(count($v)) $res[$r['id_article']][] = $v;
		}
	}
	return $res;
}
function setImages($e,$img,$singleOnly = false){
	if($e && is_array($img)
		&& ($id = $e->getAttribute('id'))
		&& isset($img[$id])
		&& is_array($img[$id])
	){
		$xml = new xml($e);
		foreach($img[$id] as $i){
			if(isset($i['img'])){
				$pic = $e->appendChild($xml->importNode($i['img'],true));
				if(isset($i['prv']))$pic->appendChild($xml->importNode($i['prv'],true));
			}elseif(isset($i['prv']))
				$e->appendChild($xml->importNode($i['prv'],true));
			if($singleOnly) break;
		}
	}
}
}