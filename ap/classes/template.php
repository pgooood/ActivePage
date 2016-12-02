<?
class template extends xml{

function __construct($path){
	parent::__construct($path);
	$this->registerNameSpace('xsl','http://www.w3.org/1999/XSL/Transform');
}
function addTemplate($path){
	if($this->de() && !$this->evaluate('count(/xsl:stylesheet/xsl:include[@href="'.htmlspecialchars($path).'"])')){
		$include = $this->de()->insertBefore(
			$this->dd()->createElementNS('http://www.w3.org/1999/XSL/Transform','xsl:include'),
			$this->de()->firstChild);
		$include->setAttribute('href',$path);
	}
}
function transform($xml_data){
	if($this->de()
		&& ($xml = $this->getXML($xml_data))
	){
		$proc = new XSLTProcessor;
		$proc->importStyleSheet($this->dd());
		return $proc->transformToXML($xml->dd());
	}
	throw new Exception('Template not found');
}
function getId(){
	return pathinfo($this->documentURI(),PATHINFO_FILENAME);
}
}
?>