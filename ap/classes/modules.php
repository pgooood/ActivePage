<?php
class modules extends taglist{
private $struct;
function __construct(xml $xml,$tagName = null){
	global $_struct;
	if(!$tagName) $tagName = 'modules';
	if($modules = $xml->query($query = '/*/'.$tagName)->item(0));
	else $modules = $xml->de()->appendChild($xml->createElement($tagName));
	parent::__construct($modules,'module');
	$this->keyAttribute = 'id';
	$this->setStructure($_struct);
}
function setStructure(structure $struct){
	$this->struct = $struct;
}
private function getObject(DOMElement $m){
	if($name = $m->getAttribute('name')){
		$name = class_exists($name) ? $name : 'module';
		return new $name($m,$this->struct);
	}
}
function add($name,$title = null,$id = null){
	if($id){
		if($this->getById($id)) return;
	}else{
		$i = 1;
		while($this->getById($id = "m$i"))$i++;
	}
	$m = $this->append(array('id'=>$id,'name'=>$name));
	if($title) $m->setAttribute('title',$title);
	return $this->getById($id);
}
function hasModule($name){
	return (bool) $this->get('@name="'.htmlspecialchars($name).'"');
}
function get($val){
	if($m = parent::get($val))
		return $this->getObject($m);
}
function getById($id){
	if($m = parent::getById($id)){
		return $this->getObject($m);
	}
}
function getByName($name){
	return $this->get('@name="'.$name.'"');
}
function remove($id){
	if($e = parent::getById($id)){
		return (bool) parent::remove($e);
	}
}
function move($val,$pos){
	$id = null;
	if(is_object($val)){
		if($val instanceof module) $id = $val->getId();
	}else $id = $val;
	if($id) parent::move(parent::getById($id),$pos);
}
function run(){
	foreach($this as $m){
		$m->run();
	}
}
/**
* Iterator
*/
function rewind(){
	if($e = parent::rewind())
		return $this->getObject($e);
}
function current(){
	if($e = parent::current())
		return $this->getObject($e);
}
function next(){
	if($e = parent::next())
		return $this->getObject($e);
}
function key(){
	return parent::key();
}
function valid(){
	return $this->current();
}
}
?>