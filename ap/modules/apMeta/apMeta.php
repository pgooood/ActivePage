<?php
class apMeta extends module{
function __construct(DOMElement $e,structure $struct){
	parent::__construct($e,$struct);
}
function getForm(){
	if($e = $this->getSection()->getXML()->query('form',$this->getRootElement())->item(0))
		return new form($e);
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
	global $_out,$_sec;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		if($form = $this->getForm()){
			$form->replaceURI(array(
				'ID' => $_sec->getId(),
				'MD' => $this->getId()
			));
			switch($action = param('action')){
				case 'save':
					$form->save($_REQUEST);
					$this->redirect('save_ok');
					break;
			}
			$form->load();
			$_out->elementIncludeTo($form->getRootElement(),'/page/section');
		}else throw new Exception('Form not found',EXCEPTION_XML);
	}
}
function install(){
	if(!$this->getForm()){
		$data_xml = new xml(PATH_MODULE.__CLASS__.'/data.xml');
		$this->getSection()->getXML()->elementIncludeTo($data_xml->getElementById('meta_form'),'//modules/module[@id="'.$this->getId().'"]');
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
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if($modules->remove($this->getId()))
			$modules->getXML()->save();
	}
	return true;
}
}