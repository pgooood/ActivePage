<?php
class apContent extends module{
protected $gallery;

function getForm(){
	if($e = $this->query('form')->item(0)){
		$xml = new xml(null,null,false);
		$form = new form($xml->appendChild($xml->importNode($e)));
		return $form;
	}
}
function getGallery(){
	if(!$this->gallery)
		$this->gallery = new xmlGallery($this);
	return $this->gallery;
}
function getFormPrepared(){
	global $_out;
	if($form = $this->getForm()){
		$ln = $_out->getLang();
		$form->replaceURI(array(
			'ID' => $this->getSection()->getId()
			,'MD' => $this->getId()
			,'PARENT' => $this->getSection()->GetParent()->getId()
			,'PATH_DATA_FOLDER_CLIENT' => ABS_PATH_DATA_CLIENT
			,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_SITE' => ABS_PATH_SITE
			,'PATH_STRUCT' => ABS_PATH_STRUCT_CLIENT
			,'PATH_IMAGE' => 'userfiles/sections/'.($ln ? $ln.'/' : null)
		));
		return $form;
	}
}
function redirect($mess = null){
	header('Location: '.ap::getUrl($mess ? array('mess' => $mess) : null));
	die;
}
function getMessage(){
	switch(param('mess')){
		case 'save_ok':
			return 'Данные успешно сохранены';
	}
}
function run(){
	global $_out;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		if($form = $this->getFormPrepared()){
			switch($action = param('action')){
				case 'save':
					$this->getGallery()->normalizeFilesPath($form);
					$values = $this->getGallery()->initImages($form,true);
					$values = array_merge($_REQUEST,$values);
					$form->save($values);
					$this->getGallery()->updateImagesSize($form);
					$this->redirect('save_ok');
					break;
				case 'fileinfo':
					if(($path = urldecode(param('path')))
						&& ($f = ap::getFileInfo(PATH_ROOT.$this->getGallery()->normalizePath($path)))
					){
						$f['path'] = PATH_ROOT.$this->getGallery()->normalizePath($path);
						$xml = new xml(null,'file',false);
						foreach($f as $tagName => $value)
							$xml->de()->appendChild($xml->createElement($tagName,null,$value));
						ap::ajaxResponse($xml);
					}
					vdump('Error file not found '.$path);
					break;
			}
			$this->getGallery()->initImages($form,false);
			$form->load();
			$_out->elementIncludeTo($form->getRootElement(),'/page/section');
		}else throw new Exception('Form not found',EXCEPTION_XML);
	}
}
function install(){
	if(!$this->getForm()
		&& ($data_xml = $this->getDataXML())
		&& ($eForm = $data_xml->getElementById('content_form'))
	){
		$this->getSection()->getXML()->elementIncludeTo($eForm,$this->getRootElement());
		if($form = $this->getForm())
			$form->getXML()->save();
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
function uninstall(){
	if($form = $this->getFormPrepared()){
		//удаляем галерею
		$formFields = $form->getFields('@type="image"');
		foreach($formFields as $ff){
			$formats = array();
			$res = $ff->query('param');
			foreach($res as $param) $formats[] = $param->parentNode->removeChild($param);
			
			$scheme = new xmlScheme();
			if(($n = $scheme->getNode($ff->getURI()))
				&& $n instanceof DOMElement
			){
				$tl = new taglist($n,'img');
				foreach($tl as $img){
					$fieldName = $ff->getName().'_IMAGE_ID_'.$img->getAttribute('id');
					foreach($formats as $param){
						$e = $ff->getRootElement()->appendChild($param->cloneNode(true));
						$e->setAttribute('name',$fieldName);
						$e->setAttribute('uri',str_replace('%IMG_NAME%',$this->getGallery()->getImageName($img->getAttribute('id')),$e->getAttribute('uri')));
					}
				}
				$ff->removeImageFiles();
				$n->parentNode->removeChild($n);
				$tl->getXML()->save();
			}
		}
	}
	
	
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if($modules->remove($this->getId()))
			$modules->getXML()->save();
		return true;
	}
}
function getDataXML(){
	if(is_file($path = PATH_MODULE.$this->getName().'/data.xml')
		|| is_file($path = PATH_MODULE.__CLASS__.'/data.xml')
	) return new xml($path);
}
function settings($action){
	global $_out;
	$xml = $this->getDataXML();
	if($e = $xml->getElementById('content_form_settings')){
		$form = new form($e);
		$form->replaceURI(array(
			'ID'=>$this->getSection()->getId()
			,'MD'=>$this->getId()
			,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_SITE' => ABS_PATH_SITE
		));
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