<?
class apBanners extends apTaglist{
function getTagList(){
	global $_site;
	$e = $_site->query('/site/banners')->item(0);
	if(!$e) $e = $_site->de()->appendChild($_site->createElement('banner'));
	return new taglist($e,'banner');
}
function getListRow($i,DOMElement $e){
	return array(
		'sort'=>$i+1,
		'title'=>$e->getAttribute('title'),
		'url'=>$e->getAttribute('url')
	);
}
function run(){
	if(ap::isCurrentModule($this)){
		switch($this->getAction()){
			case 'bannersize':
				if(($path = urldecode(param('path')))
					&& (is_file($path))
				){
					list($width,$height) = getimagesize($path);
					$xml = new xml(null,'size',false);
					$xml->de()->setAttribute('width',$width);
					$xml->de()->setAttribute('height',$height);
					ap::ajaxResponse($xml);
				}
				vdump('Error file not found '.$path);
				break;
		}
	}
	parent::run();
}
function onUpdate(){
	if($path = param('file')){
		$size = getimagesize('../'.$path);
		param('width',$size[0]);
		param('height',$size[1]);
		param('mime',$size['mime']);
	}
	return parent::onUpdate();
}
function onAdd(){
	if($path = $_REQUEST['banner']['file']){
		$size = getimagesize('../'.$path);
		$_REQUEST['banner']['width'] = $size[0];
		$_REQUEST['banner']['height'] = $size[1];
		$_REQUEST['banner']['mime'] = $size['mime'];
	}
	return parent::onAdd();
}
function install(){
	$xml_data = new xml(PATH_MODULE.$this->getName().'/data.xml');
	$xml_sec = $this->getSection()->getXML();
	$ar = array('form_edit','form_add','banner_list');
	foreach($ar as $id)
		if(($e = $xml_data->query('//*[@id="'.$id.'"]')->item(0))
			&& !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement())
		) $xml_sec->elementIncludeTo($e,$this->getRootElement());
	$xml_sec->save();
	return true;
}
function addTemplates(){
	$this->getSection()->getTemplate()->addTemplate('../../modules/'.$this->getName().'/banner.xsl');
}
}
require_once('classes/form.php');
class formBanner extends formField{
function getPath(){
	if(($path = $this->getValue())
		&& is_file($path = '../'.$path)
	) return $path;
}
function setWidth($v){
	$this->e->setAttribute('width',$v);
}
function setHeight($v){
	$this->e->setAttribute('height',$v);
}
function setType($v){
	$this->e->setAttribute('type',$v);
}
function setValue($value){
	parent::setValue($value);
	//$this->getRootElement()->setAttribute('file','../'.$value);
	if($path = $this->getPath()){
		list($width,$height,$type,$attr) = getimagesize($path);
		if($width) $this->setWidth($width);
		if($height) $this->setHeight($height);
	}
	$ar = array();
	
	//vdump(is_file($path));
}
}