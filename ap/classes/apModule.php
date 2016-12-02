<?php
class apModule extends module{
function getDataXML(){
	if(is_file($path = PATH_MODULE.$this->getName().'/data.xml')
		|| is_file($path = PATH_MODULE.__CLASS__.'/data.xml')
	) return new xml($path);
}
function getFormElement($id){
	if($e = $this->query('form'.($id ? '[@id="'.$id.'"]' : null))->item(0)){
		$xml = new xml(null,null,false);
		return $xml->appendChild($xml->importNode($e));
	}
}
function getListElement($id){
	return $this->query('rowlist[@id="'.$id.'"]')->item(0);
}
function getForm($id = null){
	if($e = $this->getFormElement($id))
		return new form($e);
}
function getRow(){
	return param('row');
}
function setRow($v){
	param('row',$v);
}
function popMessage(){
	if(!session_id()) session_start();
	$name = $this->getSection()->getId().'_'.$this->getId();
	if(isset($_SESSION['apMess'][$name])){
		$v = $_SESSION['apMess'][$name];
		unset($_SESSION['apMess'][$name]);
		return $v;
	}
}
function setMessage($mess){
	if(!session_id()) session_start();
	$_SESSION['apMess'][$this->getSection()->getId().'_'.$this->getId()] = $mess;
}
function getMessageList(){
	return array(
		'save_ok' => 'Данные успешно сохранены'
		,'save_fail' => 'Ошибка сохранения данных'
	);
}
function getMessage(){
	if(is_array($arMess = $this->getMessageList())
		&& ($mess = $this->popMessage())
		&& isset($arMess[$mess])
	) return $arMess[$mess];
}
function redirect($mess = null,$param = array()){
	$this->setMessage($mess);
	header('Location: '.ap::getUrl($param));
	die;
}
function addTemplate($moduleRelativePath){
	if(is_file($path = 'modules/'.$this->getName().'/'.$moduleRelativePath)){
		$this->getSection()->getTemplate()->addTemplate('../../'.$path);
	}else throw new Exception('Template not found: '.$path);
}
function getInstallElementIdList(){
	return null;
}
function install($client = true){
	if(($ar = $this->getInstallElementIdList())
		&& ($xml_data = $this->getDataXML())
		&& ($xml_sec = $this->getSection()->getXML())
	){
		foreach($ar as $id){
			$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
			if($e && !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
				$xml_sec->elementIncludeTo($e,$this->getRootElement());
		}
		$xml_sec->save();
	}
	if($client){
		if($sec = ap::getClientSection($this->getSection()->getId())){
			$modules = $sec->getModules();
			if(!$modules->getById($this->getId())){
				$moduleName = $this->getName();
				if(preg_match('/ap([A-Z].*)/',$moduleName,$m))
					$moduleName = strtolower($m[1]);
				$modules->add($moduleName,$this->getTitle(),$this->getId());
				$modules->getXML()->save();
				return true;
			}
		}
	}else return true;
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