<?php
class taglist implements Iterator{
private $parent;
private $keyAttribute;
public $tagName;
function __construct(DOMElement $e,$tagName = 'item'){
	$this->parent = $e;
	$this->tagName = $tagName;
	$this->setKeyAttribute('id');
}
function getRootElement(){
	return $this->parent;
}
function getXML(){
	return new xml($this->parent->ownerDocument);
}
function setKeyAttribute($attr){
	$this->keyAttribute = $attr;
}
function append($attrs = null,$value = null){
	$xml = $this->getXML();
	return $this->parent->appendChild($xml->createElement($this->tagName,$attrs,$value));
}
function getListElement(){
	return $this->parent;
}
function getList(){
	$xml = $this->getXML();
	return $xml->query($this->tagName,$this->parent);
}
function get($val){
	if(is_int($val)||is_string($val)){
		$xml = $this->getXML();
		return $xml->query($this->tagName.'['.$val.']',$this->parent)->item(0);
	}elseif($val instanceof DOMElement) return $val;
}
function getById($id,$attr = null){
	$attr = $attr ? $attr : ($this->keyAttribute ? $this->keyAttribute : 'id');
	return self::get('@'.$attr.'="'.htmlspecialchars($id).'"');
}
function getNum(){
	$res = 0;
	if($this->tagName){
		$xml = $this->getXML();
		$res = $xml->evaluate('count('.$this->tagName.')',$this->parent);
	}
	return $res;
}
static function getPos(DOMElement $e){
	$xml = new xml($e->ownerDocument);
	return $xml->evaluate('count(preceding-sibling::'.$e->tagName.')',$e);
}
function move($val,$pos){
	if($e = self::get($val)){
		$pos_cur = self::getPos($e);
		$num = $this->getNum();
		if($pos<1) $pos = 1;
		elseif($pos>$num) $pos = $num;
		if($e2 = self::get($pos + ($pos_cur<$pos ? 1 : 0)))
			return $this->parent->insertBefore($e,$e2);
		else return $this->parent->appendChild($e);
	}
}
function remove($val){
	if($e = self::get($val))
		return $this->parent->removeChild($e);
}
function removeAll(){
	$res = $this->getList();
	foreach($res as $e)
		$e->parentNode->removeChild($e);
}
function generateId($prefix = 'n'){
	$i = 1;
	while($this->getById($id = $prefix.$i)) $i++;
	return $id;
}


/**
* Iterator
*/
function rewind(){
    $this->taglist = $this->getList();
	$this->position = 0;
	return self::current();
}
function current(){
	return $this->taglist->item($this->position);
}
function key(){
	$e = self::current();
	return $this->keyAttribute && $e->hasAttribute($this->keyAttribute) ? $e->getAttribute($this->keyAttribute) : $this->position;
}
function next(){
	return $this->taglist->item(++$this->position);
}
function valid(){
	return (bool) self::current();
}
}