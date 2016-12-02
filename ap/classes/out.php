<?php
class out extends xml{
	function __construct($ln = null){
		parent::__construct(null,'page',false);
		$this->de()->setAttribute('host',$_SERVER['HTTP_HOST']);
		$this->de()->appendChild($this->createElement('section'));
		if($ln) $this->de()->setAttribute('ln',$ln);
		if(defined('BASE_URL'))
			$this->de()->setAttribute('base_url','http://'.filter_input(INPUT_SERVER,'HTTP_HOST').BASE_URL);
	}
	function getLang(){
		return $this->de()->getAttribute('ln');
	}
	function setMeta($name,$value,$readonly = false){
		$e = $this->query('/*/meta[@name="'.htmlspecialchars($name).'"]')->item(0);
		if(!$e) $e = $this->de()->appendChild($this->createElement('meta',array('name'=>$name)));
		if($e) xml::setElementText($e,$value);
		if($readonly)
			$e->setAttribute('readonly','readonly');
		elseif($e->hasAttribute('readonly'))
			$e->removeAttribute('readonly');
	}
	function setH1($v){
		if($e = $this->query('/page/section')->item(0))
			$e->setAttribute('h1',$v);
	}
	function isMetaWritable($name){
		return !$this->evaluate('string(/*/meta[@name="'.htmlspecialchars($name).'"]/@readonly)');
	}
	function addSectionContent($val){
		if($val instanceof xml){
			return $this->xmlIncludeTo($val,'/page/section');
		}elseif($val instanceof DOMElement){
			return $this->elementIncludeTo($val,'/page/section');
		}elseif($val instanceof DOMNodeList){
			$ar = array();
			foreach($val as $e) $ar[] = $this->addSectionContent($e);
			return $ar;
		}elseif(is_string($val)){
			if($e = $this->query('/page/section')->item(0)){
				return $e->appendChild($this->dd()->createTextNode($val));
			}
		}
	}
}