<?php
class apAnnounce extends apModule{
function settings($action){
	global $_out,$_struct;
	if(($xml = $this->getDataXML())
		&& ($e = $xml->getElementById('settings'))
	){
		$form = new form($e);
		$form->replaceURI(array(
			'MODULE'=>$this->getId()
			,'SECTION'=>$this->getSection()->getId()
			,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
		));
		if($ff = $form->getField('section'))
			apSectionEdit::seclist(ap::getClientStructure()->de(),$ff,$ar = array());
		switch($action){
			case 'update':
			case 'apply_update':
				$form->save($_REQUEST);
				break;
			case 'edit':
				if(($id = param('section'))
					&& ($sec = $_struct->getSection($id))
					&& ($modules = $sec->getModules())
				){
					$xml = new xml(null,'modules',false);
					foreach($modules as $m)
						$xml->de()->appendChild($xml->importNode($m->getRootElement(),false));
					ap::ajaxResponse($xml);
				}
				break;
		}
		$form->load();
		$_out->addSectionContent($form->getRootElement());
		$this->addTemplate('tpl.xsl');
	}
}
}