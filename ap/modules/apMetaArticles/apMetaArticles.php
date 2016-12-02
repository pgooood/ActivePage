<?php
class apMetaArticles extends module{
protected $table = 'articles_meta';

function run(){
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		switch(param('action')){
			case 'update':
				if($this->onUpdate())
					$this->redirect('save_ok');
				else $this->redirect('save_fail');
				break;
			default:
				$this->onDefault();
		}
	}
}
function onUpdate(){
	if($form = $this->getForm('form_edit')){
		$form->save($_REQUEST);
		return true;
	}
}
function onDefault(){
	global $_out;
	if($form = $this->getForm('form_edit')){
		if(($row = $this->getRow())
			&& ($mysql = new mysql())
			&& ($rs = $mysql->query('select * from `'.$mysql->getTableName(apArticles::tableArticles).'` where id='.$row))
			&& ($r = $mysql->fetch($rs))
		){
			$form->setTitle('Изменить данные для "'.$r['title'].'"');
		}
		$form->load();
		if($ff = $form->getField('row')){
			$ff->setValue($this->getRow());
		}
		$_out->addSectionContent($form->getRootElement());
	}
}
function getRow(){
	$id =
	$md = null;
	$row = filter_var(param('row'),FILTER_VALIDATE_INT);
	if(!$row
		&& $_SERVER['HTTP_REFERER']
		&& ($str = parse_url($_SERVER['HTTP_REFERER'],PHP_URL_QUERY))
		&& ($id = $this->getSection()->getId())
		&& ($md = $this->getConf('module',true))
	){
		parse_str($str);
	}
	return $row ? $row : 0;
}
function getConf($name,$required = false){
	$xml = new xml(PATH_ROOT.ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml');
	$v = $xml->evaluate('string(/section/modules/module[@id="'.$this->getId().'"]/config/@'.$name.')');
	if(!$v && $required) vdump('No '.$name.' has been chosen');
	return $v;
}
function getMessSessionName(){
	return $this->getSection()->getId().'_'.$this->getId();
}
function setMessage($mess){
	if($mess){
		if(!session_id() && !headers_sent()) session_start();
		$_SESSION['apMess'][$this->getMessSessionName()] = $mess;
	}
}
function getMessage(){
	if(!session_id() && !headers_sent()) session_start();
	$mess = null;
	switch($_SESSION['apMess'][$this->getMessSessionName()]){
		case 'save_ok':
			$mess = 'Данные успешно сохранены'; break;
		case 'save_fail':
			$mess = 'Ошибка сохранения'; break;
	}
	$_SESSION['apMess'] = array();
	return $mess;
}
function redirect($mess = null){
	$params = array('row' => $this->getRow());
	$this->setMessage($mess);
	header('Location: '.ap::getUrl($params));
	die;
}
function getForm($id,$isSettingsForm = false){
	if(($xml = $this->getDataXML())
		&& ($e = $xml->query('//form[@id="'.$id.'"]')->item(0))
	){
		$xml = new xml(null,null,false);
		$form = new form($xml->appendChild($xml->importNode($e)));
		$mysql = new mysql();
		$sec = $this->getSection();
		$form->replaceURI(array(
			'TABLE'=>$mysql->getTableName($this->table)
			,'MODULE'=>$this->getId()
			,'CONFIG_MODULE'=>$this->getConf('module')
			,'SECTION'=>$sec->getId()
			,'ROW'=> $isSettingsForm ? null : $this->getRow()
			,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($sec->getId()).'.xml'
			,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($sec->getId()).'.xml'
			,'PATH_SITE' => ABS_PATH_SITE
		));
		
		return $form;
	}
}
function getDataXML(){
	if(is_file($path = PATH_MODULE.$this->getName().'/data.xml') //путь к папке модуля (меняется, если наследуется другим модулем)
		|| is_file($path = PATH_MODULE.__CLASS__.'/data.xml') //всегда путь к папке этого модуля
	) return new xml($path);
}
function install(){
	$mysql = new mysql();
	if(!$mysql->hasTable($this->table)){
		$mysql->query('CREATE TABLE `'.$mysql->getTableName($this->table).'` (
`section` varchar(63) NOT NULL,
`module` varchar(15) NOT NULL,
`id_article` int(10) unsigned NOT NULL DEFAULT "0",
`title` varchar(255) DEFAULT NULL,
`keywords` text,
`description` text,
`h1` varchar(255) DEFAULT NULL,
PRIMARY KEY (`section`,`module`,`id_article`)
)');
	}
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if(!$modules->getById($this->getId())){
			$modules->add('metaArticles',$this->getTitle(),$this->getId());
			$modules->getXML()->save();
		}
		return true;
	}
}
function uninstall(){
	$mysql = new mysql();
	if($md = $this->getConf('module'))
		$mysql->query('delete from `'.$mysql->getTableName($this->table).'` where `section`="'.$this->getSection()->getID().'" AND `module`="'.$md.'"');
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if($modules->remove($this->getId()))
			$modules->getXML()->save();
		return true;
	}
}
function settings($action){
	global $_out;
	if($form =$this->getForm('form_settings',true)){
		if(($ff = $form->getField('module'))
			&& ($modules = $this->getSection()->getModules())
		){
			$ff->addOption('','…');
			foreach($modules as $m)
				$ff->addOption($m->getId(),$m->getTitle());
		}
		switch($action){
			case 'update':
			case 'apply_update':
				$form->save($_REQUEST);
				return;
		}
		$form->load();
		$_out->addSectionContent($form->getRootElement());
	}
}
}