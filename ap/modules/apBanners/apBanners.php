<?php
class apBanners extends apTaglist{
	function getTagList(){
		global $_site;
		switch($this->getRootElement()->getAttribute('dataLocation')){
			//данные в файле раздела
			case 'local':
				if(($sec = ap::getClientSection($this->getSection()->getId()))
					&& ($xml = $sec->getXml())
					&& ($eModules = $xml->query('/section/modules')->item(0))
					&& (($eModule = $xml->query('module[@id="'.$this->getId().'"]',$eModules)->item(0))
						|| ($eModule = $eModules->appendChild($xml->createElement('module',array(
							'id' => $this->getId()
							,'name' => 'banners')))
						))
					&& !($e = $xml->query('banners',$eModule)->item(0))
				){
					$e = $eModule->appendChild($xml->createElement('banners'));
				}
				break;
			//данные в файле сайта
			case 'global':
			default:
				if(!($e = $_site->query('/site/banners')->item(0)))
					$e = $_site->de()->appendChild($_site->createElement('banners'));
				break;
		}
		return new taglist($e, 'banner');
	}

	function getListRow($i, DOMElement $e){
		return array(
			'sort' => $i + 1,
			'title' => $e->getAttribute('title'),
			'url' => $e->getAttribute('url')
		);
	}

	function run(){
		if(ap::isCurrentModule($this)){
			switch($this->getAction()){
				case 'bannersize':
					if(($path = urldecode(param('path'))) && (is_file($path))
					){
						list($width, $height) = getimagesize($path);
						$xml = new xml(null, 'size', false);
						$xml->de()->setAttribute('width', $width);
						$xml->de()->setAttribute('height', $height);
						ap::ajaxResponse($xml);
					}
					vdump('Error file not found ' . $path);
					break;
			}
		}
		parent::run();
	}

	function onUpdate(){
		if($path = param('file')){
			if($size = getimagesize(PATH_ROOT . $path)){
				param('width', $size[0]);
				param('height', $size[1]);
				param('mime', $size['mime']);
			}else{
				param('mime', mime_content_type(PATH_ROOT . $path));
			}
		}
		return parent::onUpdate();
	}

	function onAdd(){
		if($path = $_REQUEST['banner']['file']){
			if($size = getimagesize(PATH_ROOT . $path)){
				$_REQUEST['banner']['width'] = $size[0];
				$_REQUEST['banner']['height'] = $size[1];
				$_REQUEST['banner']['mime'] = $size['mime'];
			}else{
				$_REQUEST['banner']['mime'] = mime_content_type(PATH_ROOT . $path);
			}
		}
		return parent::onAdd();
	}

	function getDataXML(){
		if(is_file($path = PATH_MODULE . $this->getName() . '/data.xml')
			|| is_file($path = PATH_MODULE . __CLASS__ . '/data.xml')
		){
			return new xml($path);
		}
	}

	function install(){
		$xml_data = $this->getDataXML();
		$xml_sec = $this->getSection()->getXML();
		$ar = array('form_edit', 'form_add', 'banner_list');
		foreach($ar as $id){
			if(($e = $xml_data->query('//*[@id="' . $id . '"]')->item(0))
				&& !$xml_sec->evaluate('count(./*[@id="' . $id . '"])', $this->getRootElement())
			){
				$xml_sec->elementIncludeTo($e, $this->getRootElement());
			}
		}
		$xml_sec->save();
		return true;
	}

	function addTemplates(){
		$this->getSection()->getTemplate()->addTemplate('../../modules/' . $this->getName() . '/banner.xsl');
	}

	function settings($action){
		global $_out;
		$xml = $this->getDataXML();
		if($e = $xml->getElementById('form_settings')){
			$form = new form($e);
			$form->replaceURI(array(
				'ID' => $this->getSection()->getId()
				, 'MD' => $this->getId()
				, 'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT . ap::id($this->getSection()->getId()) . '.xml'
				, 'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP . ap::id($this->getSection()->getId()) . '.xml'
				, 'PATH_SITE' => ABS_PATH_SITE
			));
			switch($action){
				case 'update':
				case 'apply_update':
					/**
					 * Перед сохранением добавляем в форму два поля со значением
					 * базовых урлов для форм в зависимости от значения в поле dataLocation
					 */
					$form->appendField(formHiddenField::create('form_add_base_uri',null
						,'file:///'.ABS_PATH_DATA_AP . ap::id($this->getSection()->getId()) . '.xml?/section/modules/module[@id="'.$this->getId().'"]/form[@id="form_add"]/@baseURI'));
					$form->appendField(formHiddenField::create('form_update_base_uri',null
						,'file:///'.ABS_PATH_DATA_AP . ap::id($this->getSection()->getId()) . '.xml?/section/modules/module[@id="'.$this->getId().'"]/form[@id="form_edit"]/@baseURI'));
					$arValues = $_REQUEST;
					switch(param('dataLocation')){
						case 'local':
							$arValues['form_add_base_uri'] = 'file:///%PATH_DATA_FILE_CLIENT%?/section/modules/module[@id=\'%MD%\' and @name=\'banners\']/banners';
							$arValues['form_update_base_uri'] = 'file:///%PATH_DATA_FILE_CLIENT%?/section/modules/module[@id=\'%MD%\' and @name=\'banners\']/banners/banner[%POSITION%]';
							break;
						case 'global':
						default:
							$arValues['form_add_base_uri'] = 'file:///%PATH_SITE%?/site/banners';
							$arValues['form_update_base_uri'] = 'file:///%PATH_SITE%?/site/banners/banner[%POSITION%]';
							break;
					}
					$form->save($arValues);
					
					/**
					 * добавляем, меняем тип или удаляем поле содержания
					 */
					$type = param('contentField');
					$xml = new xml($this->getSection()->getXml()->documentUri(),null,null);
					if($formElement = $xml->query('/section/modules/module[@id="'.$this->getId().'"]/form[@id="form_add"]')->item(0)){
						$form = new form($formElement);
						if($field = $form->getField('banner[tag_text_content]'))
							$field->remove();
						if($type)
							$form->appendField(formField::create('field',$type
								,'banner[tag_text_content]','Содержание','#banner'));
						$form->getXML()->save();
					}
					if($formElement = $xml->query('/section/modules/module[@id="'.$this->getId().'"]/form[@id="form_edit"]')->item(0)){
						$form = new form($formElement);
						if($field = $form->getField('content'))
							$field->remove();
						if($type)
							$form->appendField(formField::create('field',$type
								,'content','Содержание','/.'));
						$form->getXML()->save();
					}

					return;
			}
			$form->load();
			$_out->addSectionContent($form->getRootElement());
		}
	}
}

require_once('classes/form.php');

class formBanner extends formField{

	function getPath(){
		if(($path = $this->getValue()) && is_file($path = PATH_ROOT . $path)
		)
			return $path;
	}

	function setWidth($v){
		$this->e->setAttribute('width', $v);
	}

	function setHeight($v){
		$this->e->setAttribute('height', $v);
	}

	function setType($v){
		$this->e->setAttribute('type', $v);
	}

	function setValue($value){
		parent::setValue($value);
		if($path = $this->getPath()){
			list($width, $height, $type, $attr) = getimagesize($path);
			if($width)
				$this->setWidth($width);
			if($height)
				$this->setHeight($height);
		}
		$ar = array();
	}
}
