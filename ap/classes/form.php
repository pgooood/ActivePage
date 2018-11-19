<?php

class form{

	protected $e;
	protected $schemeCache = array();

	function __construct(DOMElement $e){
		$this->e = $e;
	}

	function setTitle($val){
		$this->e->setAttribute('title', $val);
	}

	function setURL($val){
		$this->getRootElement()->setAttribute('action', $val);
	}

	function getXML(){
		return new xml($this->e->ownerDocument);
	}

	function getRootElement(){
		return $this->e;
	}

	function clearSchemeCache(){
		$this->schemeCache = array();
	}

	function getSchemeCache(){
		return $this->schemeCache;
	}

	function getSchemeCacheObject($uri){
		if($className = form::getSchemeClassName($uri)){
			if(isset($this->schemeCache[$className]))
				return $this->schemeCache[$className];
			if($o = form::getSchemeObject($uri))
				return $this->schemeCache[$className] = $o;
		}
	}

	static function getSchemeClassName($uri){
		if(($url = parse_url($uri)) && isset($url['scheme'])){
			if($url['scheme'] == 'file' && preg_match('/[\w_]*\.([\w_]+)+/', $url['path'], $res)){
				$url['scheme'] = $res[1];
			}
			return $url['scheme'] . 'Scheme';
		}
	}

	static function getSchemeObject($uri){
		if(($className = form::getSchemeClassName($uri)) && class_exists($className))
			return new $className;
	}

	static function getBaseURI(DOMElement $e){
		while($e = $e->parentNode)
			if($e->hasAttribute('baseURI'))
				return $e->getAttribute('baseURI');
			elseif(strtolower($e->tagName) == 'form')
				break;
	}

	static function getURI(DOMElement $e){
		$uri = $e->getAttribute('uri');
		$url = parse_url($uri);
		if(!isset($url['scheme']) && ($baseURI = form::getBaseURI($e)))
			$uri = $baseURI . $uri;
		return $uri;
	}

	function load(){
		$xml = $this->getXML();
		$res = $xml->query('.//param[@uri] | .//field[@uri]', $this->e);
		$this->clearSchemeCache();
		foreach($res as $f){
			$uri = form::getURI($f);
			if($scheme = $this->getSchemeCacheObject($uri)){
				$ff = $this->getField($f);
				$ff->setValue($scheme->get($uri));
			}
		}
	}

	function save($data){
		$this->clearSchemeCache();
		$xml = $this->getXML();
		$res = $xml->query('.//field[@uri] | .//param[@uri]', $this->e);
		foreach($res as $f){
			$uri = form::getURI($f);
			if($scheme = $this->getSchemeCacheObject($uri)){
				$fieldName = $f->getAttribute('name');
				if(preg_match('/^([\w\-]+)\[([\w\-]*)\]$/', $fieldName, $matches))
					$fieldName = $matches[1];
				$val = @$data[$fieldName];

				if($f->hasAttribute('saveIfNoEmpty') && !$val)
					continue;
				if($f->hasAttribute('saveMD5')){
					if(is_array($val)){
						$data[$fieldName][$matches[2]] = $val[$matches[2]] = md5($val[$matches[2]]);
					}else{
						$val = md5($val);
					}
				}
				switch($f->getAttribute('type')){
					case 'checkbox':
						if(!$val)
							$val = 0;
						break;
					case 'number':
						if(!strlen($val) || !is_numeric($val))
							$val = null;
						break;
				}
				if(strstr($f->getAttribute('check'), 'num')){
					$val = str_replace(array(',', ' '), array('.', ''), $val);
					$val = is_numeric($val) ? floatval($val) : null;
				}
				$scheme->add($uri, $val);
			}
		}
		$schemes = $this->getSchemeCache();
		foreach($schemes as $scheme)
			$scheme->save();
	}

	static function replaceConstants($str, $arValues){
		foreach($arValues as $search => $replace)
			$str = str_replace('%' . $search . '%', $replace, $str);
		return $str;
	}

	function replaceURI($v){
		if(!is_array($v) || !count($v))
			return;
		$xml = $this->getXML();
		$res = $xml->query('//@uri | //@baseURI', $this->e);
		foreach($res as $i => $attr)
			$attr->value = str_replace('&', '&#38;', $this->replaceConstants($attr->value, $v));
	}

	function appendField($e){
		$lastField = $this->getXML()->query('.//field[' . $this->getXML()->evaluate('count(.//field)', $this->e) . ']', $this->e)->item(0);
		$ff = new formField($lastField);
		return $ff->insertAfter($e);
	}

	function getField($field){
		$xml = $this->getXML();
		if(is_object($field)){
			if($field instanceof DOMElement)
				$e = $field;
			elseif($field instanceof formField)
				return $field;
		}elseif($field){
			$e = $xml->query('.//*[(name()="field" or name()="param") and @name="' . htmlspecialchars($field) . '"]', $this->e)->item(0);
		}
		if($e){
			switch($e->getAttribute('type')){
				case 'image':
					return new formImageField($e);
				case 'multiselect':
					return new formSelect($e);
				default:
					if($e->tagName == 'param'){
						return new formHiddenField($e);
					}elseif(class_exists($classname = 'form' . ucfirst($e->getAttribute('type')))){
						return new $classname($e);
					}else
						return new formField($e);
			}
		}
	}

	function getFields($xpath = null){
		$res = $this->getXML()->query('.//*[(name()="field" or name()="param")' . ($xpath ? ' and ' . $xpath : null) . ']', $this->getRootElement());
		$ar = array();
		foreach($res as $e)
			if($ff = $this->getField($e))
				$ar[] = $ff;
		return $ar;
	}

}

/**
 * formField
 */
class formField{

	protected $e;

	function __construct(DOMElement $e){
		$this->e = $e;
	}

	static function create($tagName, $type, $fieldName, $label = null, $uri = null){
		$xml = new xml(null, $tagName);
		$xml->de()->setAttribute('type', $type);
		$xml->de()->setAttribute('name', $fieldName);
		if($label)
			$xml->de()->setAttribute('label', $label);
		if($uri)
			$xml->de()->setAttribute('uri', $uri);

		switch($type){
			case 'image':
				return new formImageField($xml->de());
			case 'multiselect':
				return new formSelect($xml->de());
			default:
				if($xml->de()->tagName == 'param'){
					return new formHiddenField($xml->de());
				}elseif(class_exists($classname = 'form' . ucfirst($type))){
					return new $classname($xml->de());
				}else
					return new formField($xml->de());
		}
	}

	function insert(formField $e, $mode = null){
		$node = $this->getXML()->dd()->importNode($e->getRootElement(), true);

		if(!$mode || $mode == "inside"){
			$this->e->parentNode->appendChild($node);
		}else if($mode == "before"){
			$this->e->parentNode->insertBefore($node, $this->e);
		}else if($mode == "after"){
			if($this->e->nextSibling){
				$this->e->parentNode->insertBefore($node, $this->e->nextSibling);
			}else{
				$this->e->parentNode->appendChild($node);
			}
		}
		$className = get_class($e);
		return new $className($node);
	}

	function insertAfter(formField $e){
		return $this->insert($e, 'after');
	}

	function insertBefore(formField $e){
		return $this->insert($e, 'before');
	}

	function getXML(){
		return new xml($this->e);
	}

	function getRootElement(){
		return $this->e;
	}

	function query($query){
		return $this->getXML()->query($query, $this->getRootElement());
	}

	function getName(){
		return $this->getRootElement()->getAttribute('name');
	}

	function getType(){
		return $this->getRootElement()->getAttribute('type');
	}

	function hasCheck($name){
		if($name && $this->getRootElement()->hasAttribute('check'))
			return (bool) strstr($this->getRootElement()->getAttribute('check'), $name);
	}

	function getURI(){
		return form::getURI($this->getRootElement());
	}

	function replaceURI($v){
		if(!$this->getRootElement()->hasAttribute('uri') || !is_array($v) || !count($v))
			return;
		$uri = $this->getRootElement()->getAttribute('uri');
		foreach($v as $search => $replace)
			$uri = str_replace('%' . $search . '%', $replace, $uri);
		$this->getRootElement()->setAttribute('uri', $uri);
	}

	function setValue($value){
		if($this->hasCheck('num') && $this->getType() != 'number')
			$value = is_numeric($value) ? str_replace('.', ',', floatval($value)) : null;
		xml::setElementText($this->e, $value);
	}

	function getValue(){
		return xml::getElementText($this->e);
	}

	function setDescription($v){
		$xml = $this->getXML();
		$e = $xml->query('desc', $this->e)->item(0);
		if(!$e)
			$e = $this->e->appendChild($xml->createElement('desc'));
		if($e){
			if($v)
				xml::setElementText($e, $v);
			else
				$e->parentNode->removeChild($e);
		}
	}

	function remove(){
		return $this->getRootElement()->parentNode->removeChild($this->getRootElement());
	}

}

/**
 * formCheckbox
 */
class formCheckbox extends formField{

	function setValue($value){
		if($value)
			$this->e->setAttribute('checked', 'checked');
		elseif($this->getValue())
			$this->e->removeAttribute('checked');
	}

	function getValue(){
		return $this->e->hasAttribute('checked');
	}

	static function create($fieldName, $label = null, $uri = null){
		return parent::create('field', 'checkbox', $fieldName, $label, $uri);
	}

}

/**
 * formHiddenField
 */
class formHiddenField extends formField{

	static function create($fieldName, $label = null, $uri = null){
		return parent::create('field', 'param', $fieldName, $label, $uri);
	}

	function setValue($value){
		$this->getRootElement()->setAttribute('value', $value);
	}

	function getValue(){
		return $this->getRootElement()->getAttribute('value');
	}

}

/**
 * formImageField
 */
class formImageField extends formField{

	static function create($fieldName, $label = null, $uri = null){
		return parent::create('field', 'image', $fieldName, $label, $uri);
	}

	static function getImagePath($uri){
		if($v = jpgScheme::parseURI($uri))
			return $v['path'];
	}

	static function imageExists($uri){
		if($path = formImageField::getImagePath($uri))
			return is_file($path);
	}

	function setValue($value){}

	function getValue(){}

	function getPreviewSize(){
		$ar = array();
		$tmp = explode('&', parse_url($this->getURI(), PHP_URL_QUERY));
		foreach($tmp as $pair){
			$v = explode('=', $pair);
			$ar[$v[0]] = $v[1];
		}
		return array('width' => isset($ar['w']) ? $ar['w'] : false
			, 'height' => isset($ar['h']) ? $ar['h'] : false
			, 'max' => isset($ar['max']) ? $ar['max'] : false
		);
	}

	function setPreviewSize($w = null, $h = null, $max = null){
		$ar = array();
		if($w)
			$ar[] = 'w=' . $w;
		if($h)
			$ar[] = 'h=' . $h;
		if($max)
			$ar[] = 'max=' . $max;
		$uri = parse_url($this->getRootElement()->getAttribute('uri'));
		$str = (isset($uri['scheme']) ? $uri['scheme'] . ':' : null)
			. (isset($uri['host']) ? '//' . $uri['host'] : null)
			. (isset($uri['path']) ? $uri['path'] : null)
			. (count($ar) ? '?' . implode('&', $ar) : null);
		$this->getRootElement()->setAttribute('uri', $str);
	}

	function removeImageFiles(){
		$res = $this->getXML()->query('param[@uri]', $this->getRootElement());
		foreach($res as $p)
			if(($path = $this->getImagePath(form::getURI($p))) && file_exists($path))
				unlink($path);
	}

}

/**
 * formSelect
 */
class formSelect extends formField{

	static function create($fieldName, $label = null, $uri = null){
		return parent::create('field', 'select', $fieldName, $label, $uri);
	}

	function setValue($value){
		$this->e->setAttribute('value', $value);
	}

	function getValue(){
		return $this->e->getAttribute('value');
	}

	function addOption($value, $text){
		$xml = new xml($this->e);
		$this->e->appendChild($xml->createElement('option', array('value' => $value), $text));
	}

}

/**
 * formTags
 */
class formTags extends formField{

	static function create($fieldName, $label = null, $uri = null){
		return parent::create('field', 'tags', $fieldName, $label, $uri);
	}

	function addTag($title){
		$xml = new xml($this->e);
		$this->e->appendChild($xml->createElement('tag', null, $title));
	}

}
