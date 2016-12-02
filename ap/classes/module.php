<?php
class module{
private $e;
private $struct;
function __construct(DOMElement $e,structure $struct){
	$this->e = $e;
	$this->setStructure($struct);
}
function getRootElement(){
	return $this->e;
}
function setStructure(structure $struct){
	$this->struct = $struct;
}
function run(){
}
function getId(){
	return $this->e->getAttribute('id');
}
function getTitle(){
	return $this->e->getAttribute('title');
}
function getName(){
	return $this->e->getAttribute('name');
}
function getSection(){
	$xml = new xml($this->e);
	if($pi = pathinfo($xml->documentURI(),PATHINFO_FILENAME))
		return $this->struct->getSection($pi);
}
function query($query){
	$xml = new xml($this->e);
	return $xml->query($query,$this->e);
}
function evaluate($query){
	$xml = new xml($this->e);
	return $xml->evaluate($query,$this->e);
}
}