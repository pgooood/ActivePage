<?
class apUploads extends apArticles{
function getMessage(){
	if(!session_id() && !headers_sent()) session_start();
	$mess = null;
	switch($_SESSION['apMess'][$this->getMessSessionName()]){
		case 'delete_ok':
			$mess = 'Файл удален'; break;
		case 'delete_fail':
			$mess = 'Ошибка, запись не удалена'; break;
		case 'update_ok':
			$mess = 'Информация успешно обновлена'; break;
		case 'update_fail':
			$mess = 'Ошибка обновления информации'; break;
		case 'add_ok':
			$mess = 'Файл добавлен'; break;
		case 'add_fail':
			$mess = 'При добавлении записи произошла ошибка'; break;
	}
	$_SESSION['apMess'] = array();
	return $mess;
}
static function getFileInfo($path){
	if(is_file($path)){
		$ar = pathinfo($path);
		$tmp = filesize($path);
		$units = array('Б','КБ','МБ');
		foreach($units as $i => $u){
			$ar['size'] = number_format($tmp,$i,',',' ').' '.$u;
			if($tmp > 1024) $tmp = $tmp/1024;
			else break;
		}
		$ar['date'] = date('d.m.Y H:i',filemtime($path));
		return $ar;
	}
}
static function normalizePath($path){
	$i = 0;
	while(0===strncmp($path,$_SERVER['PHP_SELF'],++$i));
	return substr($path,$i-1);
}
static function getAbsPath($path){
	if(!$path) return;
	$path_site = str_replace('\\','/',realpath(pathinfo($_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'],PATHINFO_DIRNAME).'/'.PATH_ROOT));
	$path_root = str_replace('\\','/',$_SERVER['DOCUMENT_ROOT']);
	$i = 0;
	$len_path_site = strlen($path_site);
	while(0===strncmp($path_site,$path_root,++$i))
		if($i>$len_path_site)break;
	return substr($path_site,$i-1).'/'.$path;
}
function getList(){
	if($res = parent::getList()){
		$res->setQueryParams(array(
			'cols' => '*,IF(`article`=\'\',`title`,CONCAT("<a href=\'../",`article`,"\'>",`title`,"</a>")) AS `file_link`'
		));
	}
	return $res;
}
function onAdd($action){
	if(($form = $this->getPreparedForm($action))
		&& ($arFields = $form->getFields('@type="file"'))
	) foreach($arFields as $ff){
		if(($path = param($ff->getName()))
			&& ($path = $this->normalizePath($path))
			&& is_file(PATH_ROOT.$path)
		)param($ff->getName(),$path);
		else param($ff->getName(),'');
	}
	return parent::onAdd($action);
}
function onUpdate($action){
	if(($form = $this->getPreparedForm($action))
		&& ($arFields = $form->getFields('@type="file"'))
	) foreach($arFields as $ff){
		$_REQUEST[$ff->getName()] = $this->normalizePath($_REQUEST[$ff->getName()]);
	}
	return parent::onUpdate($action);
}
function onEdit($action){
	$res = parent::onEdit($action);

	if(($form = $this->getPreparedForm($action))
		&& ($arFields = $form->getFields('@type="file"'))
	){
		foreach($arFields as $ff)
			$ff->setValue($this->getAbsPath($ff->getValue()));
	}
	return $res;
}
function run(){
	if(ap::isCurrentModule($this)){
		switch($action = param('action')){
			case 'fileinfo':
				if(($path = urldecode(param('path')))
					&& ($f = $this->getFileInfo($_SERVER['DOCUMENT_ROOT'].$path))
				){
					$f['path'] = $path;
					$xml = new xml(null,'file',false);
					foreach($f as $tagName => $value)
						$xml->de()->appendChild($xml->createElement($tagName,null,$value));
					ap::ajaxResponse($xml);
				}
				vdump('Error file not found '.$path);
				break;
		}
		parent::run();
	}
}
function settings($action){}
}