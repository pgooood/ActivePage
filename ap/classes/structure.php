<?
class structure extends xml{
private $data_path;
private $template_path;
function __construct($uri,$data_path = null,$template_path = null,$cache = true){
	parent::__construct($uri,'structure',$cache);
	
	$this->data_path = $data_path && is_dir($data_path) ? $data_path : pathinfo($this->documentURI(),PATHINFO_DIRNAME).'/data/';
	if(substr($this->data_path,-1)!='/') $this->data_path.= '/';
	
	$this->template_path = $template_path && is_dir($template_path) ? $template_path : pathinfo($this->documentURI(),PATHINFO_DIRNAME).'/templates/';
	if(substr($this->template_path,-1)!='/') $this->template_path.= '/';
}
function getTemplatePath(){
	return $this->template_path;
}
function getDataPath(){
	return $this->data_path;
}
function getSection($id){
	if(($sec = $this->getElementById($id))){
		return new section($sec,$this->data_path,$this->template_path);
	}
}
function getElementById($id){
	$id = htmlspecialchars($id);
	return $this->query('.//sec[@id="'.$id.'" or @alias="'.$id.'"]',$this->de())->item(0);
}
function getDefaultSectionId(){
	$defId = $this->de()->getAttribute('default');
	if(($sec = $this->getElementById($defId)) || ($sec = $this->query('/structure/sec[1]')->item(0)))
		return $sec->getAttribute('id');
}
function setDefaultSectionId($id){
	if($id) $this->de()->setAttribute('default',$id);
}
function getCurrentSection(){
	if(($id = param('id')) || ($id = $this->getDefaultSectionId())){
		if($sec = $this->getSection($id)){
			$sec->setSelected(true);
			return $sec;
		}
	}else{
		$this->de()->appendChild($this->createElement('sec',array('id'=>'home','title'=>'Home')));
		$this->save();
		if($sec = $this->getSection($id)){
			$sec->setSelected(true);
			return $sec;
		}
	}
	throw new Exception('Section not found',EXCEPTION_404);
}
function addSection($id,$title,$section = null){
	if($title && $id && !$this->getSection($id)){
		$parent = $section instanceof section ? $section->getElement() : $this->de();
		$parent->appendChild($this->createElement('sec',array('id'=>$id, 'title'=>$title)));
		return $this->getSection($id);
	}
}
function removeSection($id,$removeFile = false){
	if($sec = $this->getSection($id)){
		return $sec->remove($removeFile);
	}
}
}