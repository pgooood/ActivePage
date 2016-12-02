<?php
class apSectionEdit extends module{
function getMessage(){
	switch(param('mess')){
		case 'save':
			return _('Данные успешно сохранены');
		case 'move_fail':
			return _('Такое перемещение раздела нельзя выполнить');
		case 'newtpl_ok':
			return _('Шаблон успешно создан');
		case 'newtpl_fail':
			return _('Ошибка создания шаблона');
	}
}
function getQueryPath(){
	$query = '';
	$sec = $this->getSection();
	do{
		if($sec->getId()=='apStruct') break;
		$query = '/sec[@id="'.htmlspecialchars(ap::id($sec->getId())).'"]'.$query;
	}while($sec = $sec->getParent());
	return $query;
}
static function getForm(){
	$xml = new xml(PATH_MODULE.__CLASS__.'/form/edit.xml');
	return new form($xml->de());
}
function redirect($action,$id = null){
	$param = array();
	switch($action){
		case 'remove':
			if($id) $param['id'] = $id;
			break;
	}
	if($action) $param['mess'] = $action;
	header('Location: '.ap::getUrl($param));
	die;
}
function run(){
	global $_out,$_struct,$_sec;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		
		$action = param('action');
		$nowId = $this->getSection()->getId();
		$path = "//sec[@id='".substr($nowId,3)."']";
		$form = $this->getForm();
		
		// если это корневой раздел то показываем форму по добавлению нового раздела
		switch($action){
			case 'ajax':
				if($parent = param('parent')){
					if(($sec_parent = ap::getClientSection($parent)) || ($parent=='apStruct')){
						header('Content-type: text/xml');
						$xml = new xml(null,'seclist',false);
						$res = ap::getClientStructure()->query($parent == 'apStruct' ? '/structure/sec' : '//sec[@id="'.$sec_parent->getId().'"]/sec');
						foreach($res as $sec){
							$xml->de()->appendChild($xml->createElement('sec',array(
								'id'	=>	$sec->getAttribute('id'),
								'title'	=>	$sec->getAttribute('title'),
							)));
						}
						echo $xml;
					}
					die();
				}
				break;
			case 'remove':
				$struct = ap::getClientStructure();
				$id = $this->getSection()->getParent()->getId();
				apSectionEdit::removeSection($nowId);
				$this->redirect($action,$id);
				break;
			case 'save':
				$form->replaceURI(array('PATH' => $this->getQueryPath()));
				// сохранение атрибутов
				$form->save($_REQUEST);
				
				// перенос элемента со сменой родителя и порядка
				$struct = ap::getClientStructure(false);
				if($id = ap::getClientSection(param('id_sec'))->getId()){
					if(!($parent = $struct->query('/structure//sec[@id="'.param('parent').'"]')->item(0))){
						$parent = $struct->query('/structure')->item(0);
					}
					if($parent){
						$sec = $struct->query('/structure//sec[@id="'.$id.'"]')->item(0);
						$sec = $sec->parentNode->removeChild($sec);
						if(($pos = param('position')) && $before = $struct->query('/structure//sec[@id="'.$pos.'"]')->item(0)){
							$parent->insertBefore($sec,$before);
						}else{
							$parent->appendChild($sec);
						}
						$struct->save();
					}
				}
				
				$this->redirect($action,param('id_sec'));
				break;
			case 'newtpl':
				if(apSectionTemplate::createPackage(ap::id($this->getSection()->getId()),param('title')))
					$this->redirect('newtpl_ok');
				else $this->redirect('newtpl_fail');
				break;
			default:
				$form->replaceURI(array('PATH' => $path));
				$form->getRootElement()->setAttribute('title',str_replace("%TITLE%",$_sec->getTitle(),$form->getRootElement()->getAttribute('title')));
				
				//список разделов
				if(($ff = $form->getField('parent'))
					&& ($s = ap::getClientStructure()->getSection(ap::id($nowId)))
				){
					$ff->addOption('apStruct','Корень');
					$ar = array($s->getId());
					$p = $s->getParent();
					$this->seclist(ap::getClientStructure()->de(),$ff,$ar);
					$ff->setValue($p ? $p->getId() : 'apStruct');
				}
				
				if($ff = $form->getField('position')){
					$ff->setValue(ap::id($nowId));
				}
				
				if(!($nowId == 'apStruct'))
					$form->load();
				
				$_sec->getTemplate()->addTemplate('../../modules/'.__CLASS__.'/template/sectionedit.xsl');
				$_out->elementIncludeTo($form->getRootElement(),'/page/section');
				break;
		}
	}
}

static function removeSection($id){
	global $_struct;

	if(($cstruct = ap::getClientStructure())
		&& ($csec = $cstruct->getSection(ap::id($id)))
		&& ($sec = $_struct->getSection(ap::id($id)))
	){
		$modules = $sec->getModules();
		foreach($modules as $m)
			apModuleManager::removeModule($sec->getId(),$m->getId());
		$csec->remove(true);
		$cstruct->save();
		$sec->remove(true);
	}
}
static function seclist($e,&$ff,&$exclude){ //список разделов для селекта
	$xml = new xml($e);
	$shift = str_repeat('- ',$xml->evaluate('count(ancestor-or-self::sec)',$e));
	$res = $xml->query('sec',$e);
	foreach($res as $sec){
		if(!in_array($sec->getAttribute('id'),$exclude)){
			$ff->addOption($sec->getAttribute('id'),$shift.$sec->getAttribute('title'));
			apSectionEdit::seclist($sec,$ff,$exclude);
		}
	}
}
}
