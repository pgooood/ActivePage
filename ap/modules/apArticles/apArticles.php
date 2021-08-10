<?php
require 'formGallery.php';
class apArticles extends module{
const tableArticles = 'articles';
const tableImages = 'articles_images';
private $rl;
protected $forms;
protected $table;
protected $tableImages;
protected $pathImages;
function __construct(DOMElement $e,structure $struct){
	global $_out;
	parent::__construct($e,$struct);
	$ln = $_out->getLang();
	$this->table = apArticles::tableArticles;
	$this->tableImages = apArticles::tableImages;
	$this->pathImages = 'userfiles/articles/'.($ln ? $ln.'/' : null);
}
function getRow(){
	if($row = param('row')){
		if(is_array($row)) foreach($row as $i => $r) $row[$i] = intval($r);
		else $row = intval($row);
	}
	return $row;
}
function setRow($v){
	param('row',$v);
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
		case 'delete_ok':
			$mess = 'Статья удалена'; break;
		case 'delete_fail':
			$mess = 'Ошибка, запись не удалена'; break;
		case 'update_ok':
			$mess = 'Информация успешно обновлена'; break;
		case 'update_fail':
			$mess = 'Ошибка обновления информации'; break;
		case 'add_ok':
			$mess = 'Запись добавлена'; break;
		case 'add_fail':
			$mess = 'При добавлении записи произошла ошибка'; break;
	}
	$_SESSION['apMess'] = array();
	return $mess;
}
function redirect($mess = null,$param = array()){
	if(!is_array($param)) $param = array();
	$action = param('action');
	if($action && ($row = $this->getRow())){
		switch($action){
			case 'apply_update':
			case 'apply_add':
				$param['action'] = 'edit';
				$param['row'] = $row;
		}
	}
	if($page = param('page')) $param['page'] = $page;
	$this->setMessage($mess);
	header('Location: '.ap::getUrl($param));
	die;
}
function getForm($action){
	if(!is_array($this->forms)) $this->forms = array();
	if(isset($this->forms[$action])) return $this->forms[$action];
	switch($action){
		case 'update':
		case 'apply_update':
		case 'edit':
			$e = $this->query('form[@id="article_form_edit"]')->item(0);
			break;
		default:
			$e = $this->query('form[@id="article_form_add"]')->item(0);
			break;
	}
	if($e){
		$xml = new xml(null,null,false);
		return $this->forms[$action] = new formGallery($xml->appendChild($xml->importNode($e)));
	}
}
function getReplaceValues($values = null){
	$mysql = new mysql();
	$ar = array(
			'TABLE'=>$mysql->getTableName($this->table)
			,'MODULE'=>$this->getId()
			,'SECTION'=>$this->getSection()->getId()
			,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_IMAGE' => $this->pathImages.$this->getSection()->getId().'/'
		);
	if(is_array($values))
		return array_merge($ar,$values);
	return $ar;
}
function getPreparedForm($action){
	$isCached = isset($this->forms[$action]);
	if(($form = $this->getForm($action)) && !$isCached){
		$form->replaceURI($this->getReplaceValues(array('ID'=>$this->getRow())));
	}
	return $form;
}
function getList(){
	if(!$this->rl){
		if($list_element = $this->query('rowlist[@id="article_list"]')->item(0)){
			$this->rl = new mysqllist($list_element,array(
				'table' => $this->table,
				'cond' => '`section`="'.$this->getSection()->getID().'" AND `module`="'.$this->getId().'"',
				'page' => param('page')
			));
			$this->rl->addDateFormat('date','d.m.Y');
		}
	}
	return $this->rl;
}
function run(){
	global $_out;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		switch($action = param('action')){
			case 'active':
				if($row = $this->getRow()){
					$mysql = new mysql();
					$state = !(param('active')=='on');
					$res = $mysql->update($this->table,array(
						'active' => $state ? '1' : '0'
					),'`id`='.$row);
					if(!$res) $state = !$state;
					if(param('ajax'))
						ap::ajaxResponse($state ? 'on' : 'off');
					else $this->redirect('active_'.($res ? 'ok' : 'fail'));
				}
				break;
			case 'move':
				if(($row = $this->getRow())
					&& ($pos = param('pos'))
					&& ($rl = $this->getList())
					&& $rl->moveRow($row,$pos)
				){
					$this->redirect('move_ok');
				}else $this->redirect('move_fail');
				break;
			case 'delete':
				if($this->onDelete($action)){
					$this->redirect('delete_ok');
				}else $this->redirect('delete_fail');
				break;
			case 'update':
			case 'apply_update':
				if($this->onUpdate($action))
					$this->redirect('update_ok');
				else $this->redirect('update_fail');
				break;
			case 'add':
			case 'apply_add':
				if($this->onAdd($action))
					$this->redirect('add_ok');
				else $this->redirect('add_fail');
				break;
			case 'edit':
				if($this->onEdit($action))
					$_out->addSectionContent($this->getForm($action)->getRootElement());
				break;
			case 'new':
				if($this->onNew($action))
					$_out->addSectionContent($this->getForm($action)->getRootElement());
				break;
			default:
				if($rl = $this->getList()){
					$rl->build();
					$_out->addSectionContent($rl->getRootElement());
				}
		}
	}
}
function onNew($action){
	$form = $this->getForm($action);
	if($ff = $form->getField('date'))
		$ff->setValue(date('d.m.Y'));
	return true;
}
function onEdit($action){
	if($row = $this->getRow()){
		$form = $this->getPreparedForm($action);
		$form->load($row);
		if($ff = $form->getField('date'))
			$ff->setValue($this->dateToStr($ff->getValue()));
		return $row;
	}
}
function onAdd($action){
	$mysql = new mysql();
	$form = $this->getPreparedForm($action);
	$this->setRow($row = $mysql->getNextId($this->table));
	$values = array_merge($_REQUEST,array(
		'section' => $this->getSection()->getId(),
		'module' => $this->getId(),
		'date' => $_REQUEST['date'] ? $this->strToDate($_REQUEST['date']) : date('Y-m-d H:i:s'),
		'sort' => $this->getNextSortIndex()
	));
	$form->save($values,$row);
	return $row;
}
function onUpdate($action){
	if($row = $this->getRow()){
		$form = $this->getPreparedForm($action);
		$values = array_merge($_REQUEST,array(
			'date' => $_REQUEST['date'] ? $this->strToDate($_REQUEST['date']) : date('Y-m-d H:i:s'),
		));
		$form->save($values,$row);
	}
	return $row;
}
function onDelete($action){
	return $this->deleteRow($this->getRow());
}
function deleteRow($row){
	if($row	&& ($rl = $this->getList())){
		if(!is_array($row)) $row = array($row);
		$form = $this->getForm('edit');
		foreach($row as $id){
			$xml = new xml();
			$f = new formGallery($xml->appendChild($xml->importNode($form->getRootElement()->cloneNode(true))));
			$f->replaceURI($this->getReplaceValues(array('ID'=>$id,'TABLE'=>$rl->getTableName())));
			$f->deleteImages($id);
			$rl->deleteRow($id);
		}
		return true;
	}
}
function getNextSortIndex(){
	return $this->getNextSortIndexEx($this->getSection()->getID(),$this->getId());
}
static function getNextSortIndexEx($secionId,$moduleId){
	$mysql = new mysql();
	$index = 1;
	if($secionId && $moduleId
		&& ($rs = $mysql->query('select max(`sort`)+1 as `new_sort_index` from `'
			.$mysql->getTableName(apArticles::tableArticles).'` where `section`="'.$secionId.'" AND `module`="'.$moduleId.'"'))
		&& ($row = $mysql->fetch($rs))
		&& $row['new_sort_index']
	) $index = $row['new_sort_index'];
	return $index;
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
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`section` varchar(63) DEFAULT NULL,
`module` varchar(15) DEFAULT NULL,
`date` datetime DEFAULT NULL,
`title` varchar(255) DEFAULT NULL,
`announce` text,
`article` text,
`active` tinyint(1) unsigned DEFAULT NULL,
`sort` int(10) unsigned NOT NULL DEFAULT "1",
PRIMARY KEY (`id`),
KEY `SectionIndex` (`section`)
)');
	}
	if(!$mysql->hasTable($this->tableImages)){
		$mysql->query('CREATE TABLE `'.$mysql->getTableName($this->tableImages).'` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`id_article` int(10) unsigned DEFAULT NULL,
`field_name` varchar(31) DEFAULT NULL,
`title` varchar(255) DEFAULT NULL,
`sort` int(10) unsigned DEFAULT NULL,
`active` tinyint(1) unsigned NOT NULL DEFAULT "1",
`ext` varchar(20) DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `IndexIdArticle` (`id_article`)
)');
	}
	if($xml_data = $this->getDataXML()){
		$xml_sec = $this->getSection()->getXML();
		$ar = $this->getInstallElementIdList();
		foreach($ar as $id){
			$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
			if($e && !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
				$xml_sec->elementIncludeTo($e,$this->getRootElement());
		}
		$xml_sec->save();
	}
	
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if(!$modules->getById($this->getId())){
			$moduleName = $this->getName();
			if(preg_match('/ap([A-Z].*)/',$moduleName,$m))
				$moduleName = strtolower($m[1]);
			$modules->add($moduleName,$this->getTitle(),$this->getId());
			$modules->getXML()->save();
		}
		return true;
	}
}
function getInstallElementIdList(){
	return array('article_form_edit','article_form_add','article_list');
}
function uninstall(){
	$mysql = new mysql();
	$table = $this->table;
	if($rs = $mysql->query('select * from `'.$mysql->getTableName($table).'` where `section`="'.$this->getSection()->getID().'" AND `module`="'.$this->getId().'"'))
		while($r = mysql_fetch_array($rs)) $this->deleteRow($r['id']);
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if($modules->remove($this->getId()))
			$modules->getXML()->save();
		return true;
	}
}
function settings($action){
	global $_out;
	if(($xml = $this->getDataXML())
		&& ($e = $xml->getElementById('article_form_settings'))
	){
		$form = new form($e);
		$form->replaceURI(array(
			'MODULE'=>$this->getId()
			,'SECTION'=>$this->getSection()->getId()
			,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_IMAGE' => $this->pathImages.$this->getSection()->getId()
		));
		//размер превью
		if(($ffh = $form->getField('previewSizeH'))
			&& ($ffv = $form->getField('previewSizeV'))
			&& ($ffMaxPrev = $form->getField('previewSizeMax'))
			&& ($ffMaxImg = $form->getField('imgSizeMax'))
		){
			if(($res = $this->query('form//field[@name="image"]/param[@preview]'))
				&& ($e1 = $res->item(0))
				&& ($e2 = $res->item(1))
			){
				$ffi1 = new formImageField($e1);
				$ffi2 = new formImageField($e2);
				switch($action){
					case 'update':
					case 'apply_update':
						$ffi1->setPreviewSize(intval(param('previewSizeH')),intval(param('previewSizeV')),intval(param('previewSizeMax')));
						$ffi2->setPreviewSize(intval(param('previewSizeH')),intval(param('previewSizeV')),intval(param('previewSizeMax')));
						$ffi1->getXML()->save();
						break;
					default:
						if(is_array($s = $ffi1->getPreviewSize())){
							$ffh->setValue($s['width']);
							$ffv->setValue($s['height']);
							$ffMaxPrev->setValue($s['max']);
						}
						break;
				}
			}
			if(($res = $this->query('form//field[@name="image"]/param[not(@preview)]'))
				&& ($e1 = $res->item(0))
				&& ($e2 = $res->item(1))
			){
				$ffi1 = new formImageField($e1);
				$ffi2 = new formImageField($e2);
				switch($action){
					case 'update':
					case 'apply_update':
						$ffi1->setPreviewSize(null,null,intval(param('imgSizeMax')));
						$ffi2->setPreviewSize(null,null,intval(param('imgSizeMax')));
						$ffi1->getXML()->save();
						break;
					default:
						if(is_array($s = $ffi1->getPreviewSize())){
							$ffMaxImg->setValue($s['max']);
						}
						break;
				}
			}
		}
		//поля формы
		$isImageField = $this->evaluate('count(./form//field[@name="image"])')==2;
		$isUpdate = $action=='update' || $action=='apply_update';
		$arFields = array('date','announce','article','image');
		$arForms = array('article_form_edit','article_form_add');
		$v = param('dataFields');
		if(!is_array($v)) $v = array();
		switch($action){
			case 'update':
			case 'apply_update':
				$xmlData = $this->getSection()->getXML();
				foreach($arFields as $fieldName){
					foreach($arForms as $formId){
						$e = $this->query('form[@id="'.$formId.'"]//field[@name="'.$fieldName.'"]')->item(0);
						if(in_array($fieldName,$v)){
							if(!$e
								&& ($e = $xml->query('/data/form[@id="'.$formId.'"]//field[@name="'.$fieldName.'"]')->item(0))
								&& ($ePlace = $this->query('./form[@id="'.$formId.'"]/place[@for="'.$fieldName.'"]')->item(0))
							){
								$ePlace->parentNode->insertBefore($xmlData->importNode($e),$ePlace);
								$ePlace->parentNode->removeChild($ePlace);
								
								if(($e = $xml->query('/data/rowlist[@id="article_list"]//col[@name="'.$fieldName.'"]')->item(0))
									&& ($ePlace = $this->query('./rowlist[@id="article_list"]//place[@for="'.$fieldName.'"]')->item(0))
								){
									$ePlace->parentNode->insertBefore($xmlData->importNode($e),$ePlace);
									$ePlace->parentNode->removeChild($ePlace);
								}
								
								$xmlData->save();
								
								if($fieldName=='announce') param('announceType','textarea');
							}
						}elseif($e){
							$e->parentNode->insertBefore($xmlData->createElement('place',array('for'=>$fieldName)),$e);
							$e->parentNode->removeChild($e);
							
							if($e = $this->query('rowlist[@id="article_list"]//col[@name="'.$fieldName.'"]')->item(0)){
								$e->parentNode->insertBefore($xmlData->createElement('place',array('for'=>$fieldName)),$e);
								$e->parentNode->removeChild($e);
							}
							
							$xmlData->save();
						}
					}
				}
				break;
			default:
				if(($ff = $form->getField('dataFields'))){
					$res = $ff->query('option[@value]');
					foreach($res as $e)
						if($this->query('form[@id="'.$arForms[0].'"]//field[@name="'.$e->getAttribute('value').'"]')->item(0))
							$e->setAttribute('checked','checked');
				}
		}
		
		$isImageField = $this->evaluate('count(form//field[@name="image"])')==2 && !(!$isImageField && $isUpdate);
		if(!$isImageField){
			while($ff = $form->getField('imgNum')) $ff->remove();
			$arImgPropFields = array('previewSizeH','previewSizeV','previewSizeMax','imgSizeMax','hasTitle');
			foreach($arImgPropFields as $fieldName)
				if($ff = $form->getField($fieldName)) $ff->remove();
		}
		
		$isAnnounceField = $this->evaluate('count(form//field[@name="announce"])')==2;
		if(!$isAnnounceField){
			while($ff = $form->getField('announceType')) $ff->remove();
		}
		
		switch($action){
			case 'update':
			case 'apply_update':
				$form->save($_REQUEST);
				return;
		}
		$form->load();
		if(($ff = $form->getField('includeContent')) && !$ff->getValue())
			$ff->setValue(0);
		if(($ff = $form->getField('imgNum')) && !$ff->getValue())
			$ff->setValue(1);
		if(($ff = $form->getField('listPageSize')) && !$ff->getValue())
			$ff->setValue(10);
		if(($ff = $form->getField('pageSize')) && !$ff->getValue())
			$ff->setValue(10);
		if(($ff = $form->getField('pageParam')) && !$ff->getValue())
			$ff->setValue('page');
		if(($ff = $form->getField('tplNameList')) && !$ff->getValue())
			$ff->setValue('articles');
		if(($ff = $form->getField('tplNameText')) && !$ff->getValue())
			$ff->setValue('articlesRow');
		$_out->addSectionContent($form->getRootElement());
	}
}
static function dateToStr($val){
	return date('d.m.Y',strtotime($val));
}
static function strToDate($val){
	if(preg_match('/([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{4})/',str_replace(' ','',trim($val)),$res)){
		return $res[3].'-'.$res[2].'-'.$res[1];
	}
	return date('Y-m-d H:i:s');
}
}
