<?
class site extends xml{
private $modules;
function __construct($uri){
	parent::__construct($uri,'site');
}
function addModule($name){
	if($modules = $this->query('/site/modules')->item(0));
	else $modules = $this->de()->appendChild($this->createElement('modules'));
	$modules->appendChild($this->createElement('module',array('name'=>$name)));
}
function getEmail(){
	return $this->de()->getAttribute('email');
}
function getDomain(){
	return $this->de()->getAttribute('domain');
}
function setModules(modules $modules){
	$this->modules = $modules;
}
function getModules(){
	if(!$this->modules) $this->setModules(new modules('modules'));
	return $this->modules;
}
function getSiteInfo(){
	$xml = new xml();
	$xml->dd()->appendChild($xml->importNode($this->de()));
	$ns = $xml->query('/site/mysql | /site/users');
	foreach($ns as $n) $n->parentNode->removeChild($n);
	return $xml;
}
}
?>