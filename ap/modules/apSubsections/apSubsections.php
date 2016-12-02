<?
class apSubsections extends module{
private $rl;
function getRow(){
	return param('row');
}
function setRow($v){
	param('row',$v);
}
function getMessSessionName(){
	return $this->getSection()->getId().'_'.$this->getId();
}
function setMessage($mess){
	if($mess){
		if(!session_id() && !headers_sent()) session_start();
		$_SESSION['apMess'][$this->getMessSessionName()] = $mess;
	}
}
function getMessage(){
	if(!session_id() && !headers_sent()) session_start();
	$mess = null;
	switch($_SESSION['apMess'][$this->getMessSessionName()]){
		case 'delete_ok':
			$mess = 'Раздел удален'; break;
		case 'delete_fail':
			$mess = 'Ошибка, запись не удалена'; break;
		case 'update_ok':
			$mess = 'Информация успешно обновлена'; break;
		case 'update_fail':
			$mess = 'Ошибка обновления информации'; break;
		case 'add_ok':
			$mess = 'Раздел добавлен'; break;
		case 'add_fail':
			$mess = 'При добавлении раздела произошла ошибка'; break;
	}
	$_SESSION['apMess'] = array();
	return $mess;
}
function redirect($mess = null){
	$param = array();
	$action = param('action');
	if($action && ($row = $this->getRow())){
		switch($action){
			case 'apply_update':
			case 'apply_add':
				$param['action'] = 'edit';
				$param['row'] = $row;
		}
	}
	if($page = param('page')) $param['page'] = $page;
	$this->setMessage($mess);
	header('Location: '.ap::getUrl($param));
	die;
}
function getForm($action){
	$xml = $this->getSection()->getXML();
	$form_element = null;
	switch($action){
		case 'update':
		case 'apply_update':
		case 'edit':
			$form_element = $xml->getElementById('form_edit');
			break;
		case 'new':
		case 'add':
		case 'apply_add':
		default:
			$form_element = $xml->getElementById('form_add');
			break;
	}
	if($form_element){
		$form = new form($form_element);
		return $form;
	}
}
function getList(){
	if($list_element = $this->query('./rowlist')->item(0)){
		$res = $this->getSection()->query('sec');
		$rl = new rowlist($list_element,$res->length,param('page'));
		$s = $rl->getStartIndex();
		$f = $rl->getFinishIndex();

		foreach($res as $i => $sec){
			if($i<$s) continue;
			elseif($i>$f) break;
			$rl->addRow($sec->getAttribute('id'),array(
				'sort'=>$i+1,
				'title'=>$sec->getAttribute('title'),
			));
		}
		$rl->setFormAction(preg_replace('/&?mess=[\w_]*/','',$_SERVER['REQUEST_URI']));
		return $rl;
	}
}
function getSecPath(){
	$sec = ap::getClientSection($this->getSection()->getId());
	$out = '';
	while($sec){
		$out = "sec[@id='".$sec->getId()."']/".$out;
		$sec = $sec->getParent();
	}
	return $out;
}
function run(){
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		switch(param('action')){
			case 'move':
				if($this->onMove()) $this->redirect('move_ok');
				else $this->redirect('move_fail');
				break;
			case 'delete':
				if($this->onDelete()) $this->redirect('delete_ok');
				else $this->redirect('delete_fail');
				break;
			case 'update':
			case 'apply_update':
				if($this->onUpdate()) $this->redirect('update_ok');
				else $this->redirect('update_fail');
				break;
			case 'add':
			case 'apply_add':
				if($this->onAdd()) $this->redirect('add_ok');
				else $this->redirect('add_fail');
				break;
			case 'edit':
				$this->onEdit();
				break;
			case 'new':
				$this->onNew();
				break;
			default:
				$this->onDefault();
		}
	}
}
function onDelete(){
	if($row = $this->getRow()){
		if(!is_array($row)) $row = array($row);
		foreach($row as $id) if($id) apSectionEdit::removeSection($id);
		return true;
	}
}
function onUpdate(){
	if(($row = $this->getRow())
		&& ($form = $this->getForm('update'))
	){
		$form->replaceURI(array(
			'SUBSECTION' => $row
			,'SECTION_XPATH' => ap::getClientSection($this->getSection()->getId())->getXPath()
			,'PATH' => $this->getSecPath()
			,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_STRUCT_CLIENT' => ABS_PATH_STRUCT_CLIENT
		));
		$form->save($_REQUEST,$row);
		return true;
	}
}
function onMove(){
	if(($row = $this->getRow())
		&& ($pos = param('pos'))
		&& ($sec = ap::getClientSection($this->getSection()->getId()))
		&& ($tl = new taglist($sec->getElement(),'sec'))
		&& ($tl->move($tl->getById($row),$pos))
	){
		$tl->getXML()->save();
		return true;
	}
}
function onAdd(){
	if(($id = param('alias'))
		&& ($title = param('title'))
		&& ($tpl = $this->evaluate('string(template/@id)'))
		&& ($sec = apSectionAdd::addSection($id,$title,ap::getClientSection($this->getSection()->getId())))
	){
		apSectionTemplate::applyTemplate($id,$tpl);
		param('row',$sec->getId());
		return true;
	}
}
function onEdit(){
	global $_out;
	if($form = $this->getForm('edit')){
		if($row = $this->getRow()){
			$form->replaceURI(array(
				'SUBSECTION' => $row
				,'SECTION_XPATH' => ap::getClientSection($this->getSection()->getId())->getXPath()
				,'PATH' => $this->getSecPath()
				,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
				,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
				,'PATH_STRUCT_CLIENT' => ABS_PATH_STRUCT_CLIENT
			));
			$form->load($row);
		}
		$_out->addSectionContent($form->getRootElement());
	}
}
function onNew(){
	global $_out;
	if($form = $this->getForm('new')){
		$_out->addSectionContent($form->getRootElement());
		$this->getSection()->getTemplate()->addTemplate('../../modules/'.__CLASS__.'/tpl.xsl');
	}
}
function onDefault(){
	global $_out;
	if($rl = $this->getList()){
		$_out->addSectionContent($rl->getRootElement());
	}
}
function install(){
	if(is_file($path = PATH_MODULE.$this->getName().'/data.xml') //путь к папке модуля (меняется, если наследуется другим модулем)
		|| is_file($path = PATH_MODULE.__CLASS__.'/data.xml') //всегда путь к папке этого модуля
	){
		$xml_data = new xml($path);
		$xml_sec = $this->getSection()->getXML();
		$ar = array('form_edit','form_add','list');
		foreach($ar as $id){
			$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
			if($e && !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
				$xml_sec->elementIncludeTo($e,$this->getRootElement());
		}
		$xml_sec->save();
		return true;
	}
}
function settings($action){
	global $_out;
	$xml = new xml(PATH_MODULE.__CLASS__.'/data.xml');
	if($e = $xml->getElementById('settings')){
		$form = new form($e);
		if(($ff = $form->getField('section_template'))
			&& ($tl = apSectionTemplate::getPackages())
		){
			$val = $ff->getValue();
			foreach($tl as $e){
				$ff->addOption($e->getAttribute('id'),$e->getAttribute('title'));
				if($val==$e->getAttribute('id')) $ff->setValue($val);
			}
		}
		$form->replaceURI(array(
			'MD'=>$this->getId()
			,'SECTION'=>$this->getSection()->getId()
			,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_STRUCT_CLIENT' => ABS_PATH_STRUCT_CLIENT
		));
		switch($action){
			case 'update':
			case 'apply_update':
				$form->save($_REQUEST);
				return;
		}
		$form->load();
		$_out->addSectionContent($form->getRootElement());
	}
}
}