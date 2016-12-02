<?php
class metaArticles extends module{
	protected $table = 'articles_meta';
	function run(){
		$mysql = new mysql();
		if($row = articles::getRequestRow()){
			if(($mId = $this->getConf('module',true))
				&& ($modules = $this->getSection()->getModules())
				&& ($m = $modules->getById($mId))
				&& ($cond = $m->getDetailCondition($row))
				&& ($rs = $mysql->query('select * from `'.$mysql->getTableName('articles').'` '.$m->getTableAlias().' where '.$cond))
				&& ($rArt = $mysql->fetch($rs))
				&& ($rs = $mysql->query('select * from `'.$mysql->getTableName($this->table).'` where `id_article`='.$rArt['id']))
				&& ($r = $mysql->fetch($rs))
				//&& vdump($r)
			){
				$this->setMeta($r);
			}
		}elseif(($rs = $mysql->query('select * from `'.$mysql->getTableName($this->table).'` where '
					.'`section`="'.$this->getSection()->getId().'" and `module`="'.$this->getConf('module',true).'" and `id_article`=0'))
			&& ($r = $mysql->fetch($rs))
		){
			$this->setMeta($r);
		}
	}
	function setMeta($r){
		global $_out;
		if($v = $r['title']) $_out->setMeta('title',$v);
		if($v = $r['keywords']) $_out->setMeta('keywords',$v);
		if($v = $r['description']) $_out->setMeta('description',$v);
		if($v = $r['h1']) $_out->setH1($v);
	}
	function getConf($name,$required = false){
		$v = $this->evaluate('string(config/@'.$name.')');
		if(!$v && $required) vdump('No '.$name.' has been chosen');
		return $v;
	}
}