<?php
class apTags extends apArticles{
	const tableTagsLinks = 'articles_tags';
	function run(){
		if(ap::isCurrentModule($this)){
			switch($action = param('action')){
				case 'tags_hint':
					if($xml = $this->getTagsHintXML(param('str')))
						ap::ajaxResponse($xml);
					else ap::ajaxResponse('error');
					break;
			}
			parent::run();
		}
	}
	function saveTags($action,$articleId){
		$mysql = new mysql();
		$form = $this->getPreparedForm($action);
		$ns = $form->getFields('@type="tags"');
		foreach($ns as $ff){
			$this->removeArticleTags($articleId,$ff->getName());
			if($tagStr = param($ff->getName())){
				$ar = explode(',',$tagStr);
				if($arTagIds = $this->saveTagsAr($ar))
					$this->appendTagsToArticle($articleId,$ff->getName(),$arTagIds);
			}
		}
	}
	//Сохраняет массив тегов в таблицу тегов
	static function saveTagsAr($arTags){
		$arQuery = array();
		if(is_array($arTags)) foreach($arTags as $tag)
			if($tag = trim($tag)) $arQuery[] = mysql::str($tag);
		if(count($arQuery)){
			$mysql = new mysql();
			$mysql->query('INSERT IGNORE INTO `'.$mysql->getTableName(apTagManager::table).'` (`title`) VALUES ('.implode('),(',$arQuery).')');
			if(($rs = $mysql->query('SELECT * FROM `'.$mysql->getTableName(apTagManager::table).'` WHERE `title` IN ('.implode(',',$arQuery).')'))
				&& mysql::num_rows($rs)
			){
				$arQuery = array();
				while($r = $mysql->fetch($rs)) $arQuery[] = $r['id'];
				return $arQuery;
			}
		}
	}
	//Назначает теги, заданные массивом айди, статье и полю
	static function appendTagsToArticle($articleId,$fieldName,$arTagIds){
		if(($articleId = intval($articleId))
			&& $fieldName
			&& is_array($arTagIds)
			&& count($arTagIds)
		){
			$arQuery = array();
			$mysql = new mysql();
			foreach($arTagIds as $id) $arQuery[] = '('.$articleId.','.mysql::str($fieldName).','.$id.')';
			return $mysql->query('INSERT IGNORE INTO `'.$mysql->getTableName('articles_tags').'` (`id_article`,`field_name`,`id_tag`) VALUES '.implode(',',$arQuery));
		}
	}
	//Отменяет все назначеные для статьи теги
	static function removeArticleTags($articleId,$fieldName){
		if(($articleId = intval($articleId))
			&& $fieldName
		){
			$mysql = new mysql();
			$mysql->query('delete from `'.$mysql->getTableName('articles_tags').'` where `id_article`='.$articleId.' and `field_name`='.mysql::str($fieldName));
		}
	}
	function loadTags($action,$articleId){
		$mysql = new mysql();
		$form = $this->getPreparedForm($action);
		$ns = $form->getFields('@type="tags"');
		foreach($ns as $eField)
			if($ff = $form->getField($eField)){
				if($rs = $mysql->query('SELECT tg.`title` FROM `'.$mysql->getTableName('articles_tags').'` AS at
LEFT JOIN `'.$mysql->getTableName(apTagManager::table).'` AS tg ON at.`id_tag`=tg.`id`
WHERE `id_article`='.$articleId.' AND `field_name`='.mysql::str($ff->getName()))
				){
					while($r = $mysql->fetch($rs)) $ff->addTag($r['title']);
				}
			}
	}
	function getTagsHintXML($str){
		$mysql = new mysql();
		$ar = explode(',',$str);
		if(($tag = trim(array_pop($ar)))
			&& ($rs = $mysql->query('select * from `'.$mysql->getTableName(apTagManager::table).'` where `title` like '.mysql::str($tag.'_%').' limit 0,12'))
			&& mysql::num_rows($rs)
		){
			$xml = new xml(null,'tags');
			while($r = $mysql->fetch($rs))
				$xml->de()->appendChild($xml->createElement('tag',null,$r['title']));
			return $xml;
		}
	}
	function onAdd($action){
		if($articleId = parent::onAdd($action))
			$this->saveTags($action,$articleId);
		return $articleId;
	}
	function onUpdate($action){
		if($articleId = parent::onUpdate($action))
			$this->saveTags($action,$articleId);
		return $articleId;
	}
	function onEdit($action){
		if($articleId = parent::onEdit($action))
			$this->loadTags($action,$articleId);
		return $articleId;
	}
	function deleteRow($row){
		if($res = parent::deleteRow($row)){
			$mysql = new mysql();
			if(!is_array($row)) $row = array($row);
			$mysql->query('delete from `'.$mysql->getTableName('articles_tags').'` where `id_article` in('.implode(',',$row).')');
		}
		return $res;
	}
	function install(){
		$mysql = new mysql();
		if(!$mysql->hasTable($table = apTagManager::table)){
			$mysql->query('CREATE TABLE `'.$mysql->getTableName($table).'` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`title` varchar(127) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `iTitle` (`title`)
)');
		}
		if(!$mysql->hasTable($table = 'articles_tags')){
			$mysql->query('CREATE TABLE `'.$mysql->getTableName($table).'` (
`id_article` int(10) unsigned NOT NULL,
`field_name` varchar(31) NOT NULL,
`id_tag` int(10) unsigned NOT NULL,
PRIMARY KEY (`id_article`,`field_name`,`id_tag`),
KEY `id_article` (`id_article`),
KEY `id_tag` (`id_tag`)
)');
		}
		return parent::install();
	}
}