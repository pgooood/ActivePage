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
	) $this->values[$url['path']][$url['query']] = array('value'=>$value, 'hash'=> isset($url['fragment']) ? $url['fragment'] : null);
}
function getNode($uri){
	if(($url = parse_url($uri))
		&& isset($url['path'])
		&& isset($url['query'])
		&& !isset($url['fragment'])
	) return $this->getXML($url['path'])->query($url['query'])->item(0);
}
function get($uri){
	if($e = $this->getNode($uri)) switch(get_class($e)){
		case 'DOMAttr':
			return $e->value;
		case 'DOMDocument':
		case 'DOMElement':
			return xml::getElementText($e);
	}
}
static function getSafe($uri){
	if($xml = xmlScheme::getXML(parse_url($uri,PHP_URL_PATH))){
		$elemToCreate = array();
		$arQuery = explode('/',parse_url($uri,PHP_URL_QUERY));
		$elem = null;
		while($query = implode('/',$arQuery)){
			if($elem = $xml->query($query)->item(0)) break;
			else array_unshift($elemToCreate,array_pop($arQuery));
		}
		if(!$elem) $elem = $xml->dd();
		foreach($elemToCreate as $str){
			if(preg_match('/^([\w\-]+)(?:\[((?:@[\w\-]+|@[\w\-]+=["\'][^"\']+["\']|\s+|and|or|[0-9])+)\]){0,1}$/',$str,$res)){
				$elem = $elem->appendChild($xml->createElement($res[1]));
				if(isset($res[2])){
					$tmp = explode(' ',$res[2]);
					//все атрибуты в условии запроса, у которых заданы значения, будут созданы
					foreach($tmp as $str) if(preg_match('/^@([\w\-]+)=["\']([^"\']+)["\']$/',trim($str),$res))
						$elem->setAttribute($res[1],$res[2]);
				}
			}else break;
		}
		return $elem;
	}
}
function save(){
	$files = array();
	foreach($this->values as $fpath => $values){
		if(!isset($files[$fpath])) $files[$fpath] = $this->getXML($fpath,null,false);
		$xml = $files[$fpath];
		foreach($values as $xpath => $data){
			
			//создаем недостающие элементы из xpath запроса
			$elemToCreate = array();
			$arQuery = explode('/',$xpath);
			$elem = null;
			while($query = implode('/',$arQuery)){
				if($elem = $xml->query($query)->item(0)) break;
				else array_unshift($elemToCreate,array_pop($arQuery));
			}
			if(!$elem) $elem = $xml->dd();
			foreach($elemToCreate as $str){
				if(preg_match('/^([\w\-]+)(?:\[((?:@[\w\-]+|@[\w\-]+=["\'][^"\']+["\']|\s+|and|or|[0-9])+)\]){0,1}$/',$str,$res)){
					$elem = $elem->appendChild($xml->createElement($res[1]));
					if(isset($res[2])){
						$tmp = explode('and',$res[2]);
						//все атрибуты в условии запроса, у которых заданы значения, будут созданы
						foreach($tmp as $str) if(preg_match('/^@([\w\-]+)=["\']([^"\']+)["\']$/',trim($str),$res)){
							$elem->setAttribute($res[1],$res[2]);
						}
					}
				}else break;
			}
			
			//Устанавливаем значения. Сначала получим целевой элемент
			if(($xpath=='/' && ($e = $xml->dd()))
				|| ($e = $xml->query($xpath)->item(0))
				|| (preg_match('/(.*)\/@([\w\-]+)$/',$xpath,$matches) //если конечная цель атрибут
					&& ($tmp = $xml->query($matches[1])->item(0))
					&& ($e = $tmp->setAttribute($matches[2],null)) //создаем его
				)
			){
				switch($elementClass = get_class($e)){
					case 'DOMAttr':
						if($data['value']){
							if(!is_array($data['value'])) $e->value = $data['value'];
						}elseif($e->ownerElement->hasAttribute($e->name))
							$e->ownerElement->removeAttribute($e->name);
						break;
					case 'DOMDocument':
					case 'DOMElement':
						if($data['hash']){
							if(preg_match('/^([\w\-]+)(?:\[((?:@[\w\-]+|@[\w\-]+=["\'][\w\-]+["\']|\s+|and)+)\]){0,1}$/',$data['hash'] = trim($data['hash']),$res)){
								$elem = $e->appendChild($xml->createElement($res[1],null,is_array($data['value']) ? null : $data['value']));
								if(isset($res[2])){
									$tmp = explode('and',$res[2]);
									foreach($tmp as $attr) if(preg_match('/^@([\w\-]+)=["\']([\w\-]+)["\']$/',trim($attr),$res)){
										$elem->setAttribute($res[1],$res[2]);
									}
								}
								if(is_array($data['value'])){
									foreach($data['value'] as $name => $value){
										if(!is_numeric($name)){
											if($value)
												$elem->setAttribute($name,$value);
											elseif($elem->hasAttribute($name))
												$elem->removeAttribute($name);
										}
									}
								}
							}
						}else{
							if(is_array($data['value'])){
								foreach($data['value'] as $name => $value){
									if(!is_numeric($name)){
										if($value)
											$elem->setAttribute($name,$value);
										elseif($elem->hasAttribute($name))
											$elem->removeAttribute($name);
									}
								}
							}else xml::setElementText($elem,$data['value']);
						}
						break;
				}
			}
		}
		$xml->save();
	}
}
}