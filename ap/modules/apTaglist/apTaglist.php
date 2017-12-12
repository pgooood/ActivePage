<?
class apTaglist extends module{
function getRow(){
	return param('row');
}
function setRow($v){
	return param('row',$v);
}
function getAction(){
	return param('action');
}
function getMessSessionName(){
	return $this->getSection()->getId().'_'.$this->getId();
}
function setMessage($mess){
	if($mess){
		if(!session_id()) session_start();
		$_SESSION['apMess'][$this->getMessSessionName()] = $mess;
	}
}
function getMessage(){
	if(!session_id()) session_start();
	$mess = null;
	switch($_SESSION['apMess'][$this->getMessSessionName()]){
		case 'delete_ok':
			$mess = 'Запись удалена'; break;
		case 'delete_fail':
			$mess = 'Ошибка, запись не удалена'; break;
		case 'update_ok':
			$mess = 'Информация успешно обновлена'; break;
		case 'update_fail':
			$mess = 'Ошибка обновления информации'; break;
		case 'add_ok':
			$mess = 'Запись добавлена'; break;
		case 'add_fail':
			$mess = 'При добавлении произошла ошибка'; break;
	}
	$_SESSION['apMess'] = array();
	return $mess;
}
function redirect($mess = null){
	$param = array();
	if(($action = $this->getAction())
		&& ($row = $this->getRow())
	) switch($action){
		case 'apply_update':
		case 'apply_add':
			$param['action'] = 'edit';
			$param['row'] = $row;
	}
	if($page = param('page')) $param['page'] = $page;
	$this->setMessage($mess);
	header('Location: '.ap::getUrl($param));
	die;
}
function getForm(){
	$xml = $this->getSection()->getXML();
	$e = null;
	switch($this->getAction()){
		case 'update':
		case 'apply_update':
		case 'edit':
			$e = $this->query('form[@id="form_edit"]')->item(0);
			break;
		case 'new':
		case 'add':
		case 'apply_add':
		default:
			$e = $this->query('form[@id="form_add"]')->item(0);
			break;
	}
	if($e){
		$form = new form($e);
		$form->replaceURI(array(
			'ID' => $this->getSection()->getId()
			,'MD' => $this->getId()
			,'PARENT' => $this->getSection()->GetParent()->getId()
			,'PATH_DATA_FOLDER_CLIENT' => ABS_PATH_DATA_CLIENT
			,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
			,'PATH_SITE' => ABS_PATH_SITE
			,'PATH_STRUCT' => ABS_PATH_STRUCT_CLIENT
		));
		return $form;
	}
}
function getListRow($i,DOMElement $e){
	return array(
		'sort'=>$i+1,
		'login'=>$e->getAttribute('login'),
		'pass'=>$e->getAttribute('pass'),
		'active'=>$e->hasAttribute('disabled')
	);
}
function getList(){
	$xml = $this->getSection()->getXML();
	if(($e = $this->query('rowlist')->item(0))
		&& ($tl = $this->getTagList())
	){
		$rl = new rowlist($e,$tl->getNum(),param('page'));
		$s = $rl->getStartIndex();
		$f = $rl->getFinishIndex();
		$i = 0;
		foreach($tl as $e){
			if($i>=$s){
				if($i>$f) break;
				$rl->addRow($i+1,$this->getListRow($i,$e));
			}
			$i++;
		}
		$rl->setFormAction(preg_replace('/&?mess=[\w_]*/','',$_SERVER['REQUEST_URI']));
		return $rl;
	}
}
function getTagList(){
	$xml = new xml('xml/list.xml','row',false);
	return new taglist($xml->de(),'row');
}
function run(){
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		switch($this->getAction()){
			case 'active':
				if($this->onActive())
					$this->redirect('active_ok');
				else $this->redirect('active_fail');
				break;
			case 'move':
				if($this->onMove())
					$this->redirect('move_ok');
				else $this->redirect('move_fail');
				break;
			case 'delete':
				if($this->onDelete())
					$this->redirect('delete_ok');
				else $this->redirect('delete_fail');
				break;
			case 'update':
			case 'apply_update':
				if($this->onUpdate())
					$this->redirect('update_ok');
				else $this->redirect('update_fail');
				break;
			case 'add':
			case 'apply_add':
				if($this->onAdd())
					$this->redirect('add_ok');
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
function onActive(){
	if(($row = $this->getRow())
		&& ($tl = $this->getTagList())
		&& ($e = $tl->get($row))
	){
		if(param('active')=='on') $e->setAttribute('disabled','disabled');
		elseif($e->hasAttribute('disabled')) $e->removeAttribute('disabled');
		$tl->getXML()->save();
		if(param('ajax'))
			ap::ajaxResponse($e->hasAttribute('disabled') ? 'off' : 'on');
		else return true;
	}
}
function onMove(){
	if(($row = $this->getRow())
		&& ($pos = param('pos'))>0
		&& ($tl = $this->getTagList())
		&& ($e = $tl->get($row))
		&& $tl->move($e,$pos)
	){
		$tl->getXML()->save();
		return true;
	}
}
function onDelete(){
	if(($row = $this->getRow())
		&& ($tl = $this->getTagList())
	){
		$num = $tl->getNum();
		if(!is_array($row)) $row = array($row);
		$ar = array();
		foreach($row as $id) $ar[] = $tl->get($id);
		foreach($ar as $e) if($e) $e->parentNode->removeChild($e);
		$tl->getXML()->save();
		return $num > $tl->getNum();
	}
}
function onUpdate(){
	if(($row = $this->getRow())
		&& ($form = $this->getForm())
		&& ($tl = $this->getTagList())
		&& ($e = $tl->get($row))
	){
		$form->replaceURI(array('POSITION'=>$row));
		$form->save($_REQUEST);
		return true;
	}
}
function onAdd(){
	if(($form = $this->getForm())
		&& ($tl = $this->getTagList())
	){
		$num = $tl->getNum();
		$form->save($_REQUEST);
		$tl = $this->getTagList();
		if($num < $tl->getNum()){
			$this->setRow($tl->getNum());
			return true;
		}
	}
}
function onEdit(){
	global $_out;
	if(($row = $this->getRow())
		&& ($form = $this->getForm())
	){
		$form->replaceURI(array('POSITION'=>$row));
		$form->load();
		if($ff = $form->getField('row'))
			$ff->setValue($row);
		$_out->elementIncludeTo($form->getRootElement(),'/page/section');
	}
	$this->addTemplates();
}
function onNew(){
	global $_out;
	if($form = $this->getForm())
		$_out->elementIncludeTo($form->getRootElement(),'/page/section');
	$this->addTemplates();
}
function onDefault(){
	global $_out;
	if($rl = $this->getList())
		$_out->elementIncludeTo($rl->getRootElement(),'/page/section');
	$this->addTemplates();
}
function addTemplates(){}
}