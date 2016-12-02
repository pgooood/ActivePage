<?
class xml{
private $dd;
private $xpc;
private $cached;
function __construct($val = null,$name = null,$cache = true){
	xml::initCache();
	$this->cached = $cache;
	if(is_object($val)){
		if($val instanceof DOMDocument){
			$this->dd = $val;
		}elseif($val instanceof DOMElement){
			$this->dd = $val->ownerDocument;
		}
	}elseif($val && is_string($val)){
		if(strtolower(substr(trim($val),0,5))=='<?xml'){
			$this->dd = DOMDocument::loadXML($val);
		}elseif($path = $this->normalizePath($val)){
			if(file_exists($path)){
				if($cache && $this->isCached($path)){
					$this->dd = xml::getCache($path);
				}else{
					$this->dd = @DOMDocument::load($path);
					if(strstr($this->dd->documentURI,'%'))
						$this->dd->documentURI = urldecode($this->dd->documentURI);
				}
			}elseif($cache && $this->isCached($path)){
				$this->dd = xml::getCache($path);
			}
		}
	}
	if(!$this->dd){
		$this->dd = new DOMDocument('1.0', 'utf-8');
		if(is_string($val))
			$this->dd->documentURI = $this->normalizePath($val);
	}
	if(!$this->de() && $name)
		$this->dd->appendChild($this->createElement($name));
	
	if($cache && $this->documentURI()){
		xml::setCache($this->dd);
	}
}
static function initCache(){
	global $_xmlCache;
	if(!isset($_xmlCache) || !is_array($_xmlCache)) $_xmlCache = array();
}
function isCached($path = null){
	global $_xmlCache;
	$uri = $path ? xml::normalizePath($path) : $this->documentURI();
	$cache = $path ? true : $this->cached;
	return $cache && isset($_xmlCache[$uri]);
}
static function clearCache($path){
	global $_xmlCache;
	if(isset($_xmlCache[xml::normalizePath($path)]))
		unset($_xmlCache[xml::normalizePath($path)]);
}
static function setCache($val){
	global $_xmlCache;
	$dd = null;
	if($val instanceof DOMDocument) $dd = $val;
	elseif($val instanceof xml) $dd = $val->dd();
	if($dd && ($uri = xml::normalizePath(xml::fixUri($dd->documentURI)))){
		$_xmlCache[$uri] = $dd;
	}
}
static function getCache($path){
	global $_xmlCache;
	if(($uri = xml::normalizePath($path)) && isset($_xmlCache[$uri]))
		return $_xmlCache[$uri];
}
static function normalizePath($path){
	if($path && (!($url = parse_url($path)) || !isset($url['scheme']))){
		if(substr($path,0,1)!='/'){
			$scriptDir = pathinfo($_SERVER['SCRIPT_FILENAME'],PATHINFO_DIRNAME);
			$arScriptDir = explode('/',$scriptDir);
			$arPath = explode('/',$path);
			foreach($arPath as $folder){
				if($folder=='..') array_pop($arScriptDir);
				elseif($folder) array_push($arScriptDir,$folder);
			}
			$path = implode('/',$arScriptDir);
		}
		$path = 'file://'.(substr($path,0,1)!='/' ? '/' : null).$path;
	}
	return $path;
}
function dd(){
	return $this->dd;
}
function de(){
	return $this->dd->documentElement;
}
static function fixUri($v){
	$m = null;
	if(preg_match('/file:\/([^\/]+.*)$/',$v,$m))
		$v = 'file:///'.$m[1];
	return $v;
}
function documentURI($val = null){
	if($uri = xml::normalizePath($val))
		$this->dd->documentURI = $uri;
	return xml::fixUri($this->dd->documentURI);
}
function appendChild($e){
	if(!$this->dd->documentElement && $e instanceof DOMNode)
		return $this->dd->appendChild($e);
}
function removeChild(){
	if($this->de())
		return $this->dd()->removeChild($this->de());
}
function importNode(DOMNode $n,$deep = true){
	return $this->dd->importNode($n,$deep);
}
function save($uri = null){
	$olduri = $this->documentURI();
	if(!$uri) $uri = $this->documentURI();
	if($uri){
		if(!file_exists($uri) && is_writable(pathinfo($uri,PATHINFO_DIRNAME)))
			fclose(fopen($uri,'w+'));
		if(file_exists($uri) && is_writable($uri)){
			if(!parse_url($uri,PHP_URL_SCHEME)) $uri = realpath($uri);
			$res = $this->dd->save($uri);
			if($res){
				$this->documentURI($uri);
				if($this->isCached($olduri)){
					xml::clearCache($olduri);
					xml::setCache($this);
				}
			}
			return $res;
		}
	}
	return false;
}
function query($query,$e = null){
	if(!$this->xpc && $this->dd) $this->xpc = new DOMXPath($this->dd);
	if($e) return $this->xpc->query($query,$e);
	if(!($res = $this->xpc->query($query)))
		throw new Exception('Bad xpath - '.$query);
	return $res;
}
function evaluate($query,$e = null){
	if(!$this->xpc && $this->dd) $this->xpc = new DOMXPath($this->dd);
	if($e) return $this->xpc->evaluate($query,$e);
	return $this->xpc->evaluate($query);
}
function getElementById($id,$attribute = null,$parent = null){
	if(!$attribute) $attribute = 'id';
	return $this->query('//*[@'.$attribute.'="'.htmlspecialchars($id).'"]',$parent)->item(0);
}
function createTextNode($val){
	return $this->dd->createTextNode($val);
}
function createElement($name,$attrs = null,$text = null){
	$e = $this->dd->createElement($name);
	if(is_array($attrs)) foreach($attrs as $attr => $value) if($value!==null) $e->setAttribute($attr,$value);
	if($text) $e->appendChild($this->createTextNode($text));
	return $e;
}
function xmlIncludeTo($doc,$cont){
	if(is_string($cont))
		$cont = $this->query($cont)->item(0);
	if(($xml = $this->getXML($doc))
		&& is_object($cont)
	){
		return $cont->appendChild($this->importNode($xml->de(),true));
	}
	throw new Exception('XML ('.print_r($doc,true).') не найден',EXCEPTION_XML);
}
function elementIncludeTo($elem,$cont){
	if(is_string($cont))
		$cont = $this->query($cont)->item(0);
	if(is_object($elem)
		&& is_object($cont)
		&& $elem instanceof DOMElement
		&& $cont instanceof DOMElement
	){
		$xml = new xml($cont);
		return $cont->appendChild($xml->importNode($elem,true));
	}
	throw new Exception('XML->elementIncludeTo() wrong element type',EXCEPTION_XML);
}
function xmlInclude($doc){
	return $this->xmlIncludeTo($doc,$this->de());
}
function __toString(){
	return $this->dd->saveXML();
}
function registerNameSpace($prefix,$uri){
	if(!$this->xpc && $this->dd) $this->xpc = new DOMXPath($this->dd);
	return $this->xpc->registerNamespace($prefix,$uri); 
}
static function getXML($xml){
	if(is_object($xml)){
		if($xml instanceof xml || is_subclass_of($xml,'xml')) return $xml;
	}elseif($xml && is_string($xml) && file_exists($xml))
		return new xml($xml);
	return null;
}
static function getElementText(DOMElement $e){
	$text = '';
	if($e->hasChildNodes()) foreach($e->childNodes as $node){
		if('DOMText'!=get_class($node)) continue;
		$text.= $node->data;
	}
	return $text;
}
static function setElementText(DOMElement $e,$text){
	if($e->hasChildNodes()) foreach($e->childNodes as $node){
		if('DOMText'!=get_class($node)) continue;
		$e->removeChild($node);
	}
	return $e->appendChild($e->ownerDocument->createTextNode($text));
}
}