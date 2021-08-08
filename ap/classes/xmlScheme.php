<?php

class xmlScheme{

	private $values = array();

	static function getXML($uri){
		$xml = new xml(PATH_ROOT.substr($uri,1),null,false);
		return $xml;
	}

	function add($uri,$value){
		if(($url = parse_url($uri))
			&& isset($url['path'])
			&& isset($url['query'])
		){
			$this->values[$url['path']][$url['query']] = array(
				'value' => $value
				,'hash' => isset($url['fragment']) ? $url['fragment'] : null
			);
		}
	}

	function getNode($uri){
		if(($url = parse_url($uri))
			&& isset($url['path'])
			&& isset($url['query'])
			&& !isset($url['fragment'])
		){
			return $this->getXML($url['path'])->query($url['query'])->item(0);
		}
	}

	function get($uri){
		if($e = $this->getNode($uri))
			switch(get_class($e)){
				case 'DOMAttr':
					return $e->value;
				case 'DOMDocument':
				case 'DOMElement':
					return xml::getElementText($e);
			}
	}

	static function getSafe($uri){
		if($xml = xmlScheme::getXML(parse_url($uri,PHP_URL_PATH)))
			return $this->makePathComplete(parse_url($uri,PHP_URL_QUERY),$xml);
	}
	
	/**
	 * Создает недостающие элементы в заданном xpath запросе
	 * @param string $xpath
	 * @param xml $xml
	 * @return boolean
	 */
	protected static function makePathComplete($xpath,$xml){
		$matches = null;
		$elemToCreate = array();
		$arQuery = explode('/',$xpath);
		$elem = null;
		while($query = implode('/',$arQuery)){
			if($elem = $xml->query($query)->item(0))
				break;
			else
				array_unshift($elemToCreate,array_pop($arQuery));
		}
		if(!$elem)
			$elem = $xml->dd();
		foreach($elemToCreate as $str){
			if(preg_match('/^([\w\-]+)(?:\[((?:@[\w\-]+|@[\w\-]+=["\'][^"\']+["\']|\s+|and|or|[0-9])+)\]){0,1}$/',$str,$matches)){
				$elem = $elem->appendChild($xml->createElement($matches[1]));
				if(isset($matches[2])){
					$tmp = explode(' and ',$matches[2]);
					//все атрибуты в условии запроса, у которых заданы значения, будут созданы
					foreach($tmp as $str)
						if(preg_match('/^@([\w\-]+)=["\']([^"\']+)["\']$/',trim($str),$matches))
							$elem->setAttribute($matches[1],$matches[2]);
				}
			}else
				break;
		}
		return $elem;
	}
	
	/**
	 * Создает новый элемент заданный в URL
	 * @param string $newElementDefinition
	 * @param DOMElement $parentElement
	 * @param xml $xml
	 * @return DOMElement|null
	 */
	protected function createElement($newElementDefinition,$parentElement,$xml){
		$matches = null;
		$newElementDefinition = trim($newElementDefinition);
		if(preg_match('/^([\w\-]+)(?:\[((?:@[\w\-]+|@[\w\-]+=["\'][\w\-]+["\']|\s+|and)+)\]){0,1}$/',$newElementDefinition,$matches)
			&& ($elem = $parentElement->appendChild($xml->createElement($matches[1])))
		){
			if(isset($matches[2]) && ($tmp = explode('and',$matches[2])))
				foreach($tmp as $attr)
					if(preg_match('/^@([\w\-]+)=["\']([\w\-]+)["\']$/',trim($attr),$matches))
						$elem->setAttribute($matches[1],$matches[2]);
			return $elem;
		}
	}
	
	/**
	 * Устанавливает блок значений связанный с элементом
	 * @param string $elem
	 * @param array $arValues
	 * @param xml $xml
	 * @return DOMElement|null
	 */
	protected function setBunchValues(DOMElement $elem,$arValues,$xml){
		if(is_array($arValues)){
			foreach($arValues as $name => $value){
				if(!is_numeric($name)){
					if('/' === substr($name,0,1))
						$this->setValue($elem->getNodePath().$name,array('value' => $value,'hash' => null),$xml);
					elseif($name == 'tag_text_content')
						xml::setElementText($elem,$value);
					elseif(mb_strlen($value))
						$elem->setAttribute($name,$value);
					elseif($elem->hasAttribute($name))
						$elem->removeAttribute($name);
				}
			}
		}
	}
	
	protected function setValue($xpath,$data,$xml){
		self::makePathComplete($xpath,$xml);
		$matches = null;
		//Устанавливаем значения. Сначала получим целевой элемент
		if(($xpath == '/' && ($e = $xml->dd()))
			|| ($e = $xml->query($xpath)->item(0))
			|| (preg_match('/(.*)\/@([\w\-]+)$/',$xpath,$matches) //если конечная цель атрибут
					&& ($tmp = $xml->query($matches[1])->item(0))
					&& ($e = $tmp->setAttribute($matches[2],null)) //создаем его
				)
		){
			switch($elementClass = get_class($e)){
				case 'DOMAttr':
					if(!is_array($data['value']) && mb_strlen($data['value']))
						$e->value = $data['value'];
					elseif(empty($data['value']))
						$e->ownerElement->removeAttribute($e->name);
					break;
				case 'DOMDocument':
				case 'DOMElement':
					if($data['hash'])
						$elem = $this->createElement($data['hash'],$e,$xml);
					else
						$elem = $e instanceof DOMDocument ? $e->documentElement : $e;
					if($elem){
						if(is_array($data['value']))
							$this->setBunchValues($elem,$data['value'],$xml);
						else
							xml::setElementText($elem,$data['value']);
					}
					break;
			}
		}
	}

	function save(){
		$files = array();
		foreach($this->values as $fpath => $values){
			if(!isset($files[$fpath]))
				$files[$fpath] = $this->getXML($fpath,null,false);
			if($xml = $files[$fpath]){
				foreach($values as $xpath => $data)
					$this->setValue($xpath,$data,$xml);
				$xml->save();
			}
		}
	}

}
