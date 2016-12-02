<?
require 'formFieldGallery.php';
class formGallery extends form{
function __construct(DOMElement $e){
	parent::__construct($e);
}
function getImageFields(){
	$xml = $this->getXML();
	$res = $xml->query('.//field[@type="image"]',$this->getRootElement());
	$ffg = array();
	foreach($res as $f)
		if($ff = $this->getField($f->getAttribute('name')))
			$ffg[] = new formFieldGallery($ff);
	return $ffg;
}
function save($values,$row){
	$ffg = $this->getImageFields();
	foreach($ffg as $f){
		$values = $f->prepareUpdate($row,$values);
	}
	parent::save($values);
}
function load($row){
	$ffg = $this->getImageFields();
	foreach($ffg as $f) $f->prepareEdit($row);
	parent::load();
}
function deleteImages($row){
	$ffg = $this->getImageFields();
	foreach($ffg as $f) $f->deleteImages($row);
}
}