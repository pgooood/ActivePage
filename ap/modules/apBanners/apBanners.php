<?php
class apBanners extends apTaglist{
	protected $itemTagName = 'item';

	function getTagList(){
		if(($eRowList = $this->query('rowlist')->item(0))
			&& ($uri = $eRowList->getAttribute('parentUri'))
			&& ($listTagName = $eRowList->getAttribute('listName'))
			&& ($uri = form::replaceConstants($uri,array(
				'ID' => $this->getSection()->getId()
				,'MD' => $this->getId()
				,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
				,'PATH_SITE' => ABS_PATH_SITE
				,'PATH_STRUCT' => ABS_PATH_STRUCT_CLIENT
			)))
			&& ($eParent = xmlScheme::getSafe($uri))
		){
			$xml = new xml($eParent);
			if(!($e = $xml->query($listTagName,$eParent)->item(0)))
				$e = $eParent->appendChild($xml->createElement($listTagName));
			if($itemName = $eRowList->getAttribute('itemName'))
				$this->itemTagName = $itemName;
			if($e)
				return new taglist($e,$this->itemTagName);
		}
		throw new Exception('The list element has not been created');
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
		if($path = $_REQUEST[$this->itemTagName]['file']){
			$size = getimagesize('../'.$path);
			$_REQUEST[$this->itemTagName]['width'] = $size[0];
			$_REQUEST[$this->itemTagName]['height'] = $size[1];
			$_REQUEST[$this->itemTagName]['mime'] = $size['mime'];
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

		if($sec = ap::getClientSection($this->getSection()->getId())){
			$modules = $sec->getModules();
			if(!$modules->getById($this->getId())){
				$modules->add('content',$this->getTitle(),$this->getId());
				$modules->getXML()->save();
			}
		}
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
		if($path = $this->getPath()){
			list($width,$height,$type,$attr) = getimagesize($path);
			if($width) $this->setWidth($width);
			if($height) $this->setHeight($height);
		}
	}
}