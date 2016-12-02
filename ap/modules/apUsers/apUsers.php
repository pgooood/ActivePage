<?php
class apUsers extends module{
function getRow(){
	if(is_array($v = param('user')))
		return @$v['login'];
	elseif($v = param('row')) return $v;
	elseif($v = param('login')) return $v;
}
function getMessage(){
	switch(param('mess')){
		case 'delete_ok':
			return 'Пользователь удален';
		case 'delete_fail':
			return 'Ошибка, пользователь не удален';
		case 'update_ok':
			return 'Информация о пользователе успешно обновлена';
		case 'update_fail':
			return 'Ошибка обновления информации';
		case 'add_ok':
			return 'Новый пользователь добавлен';
		case 'add_fail':
			return 'При добавлении пользователя произошла ошибка';
	}
}
function redirect($mess = null){
	$param = array();
	
	if(($action = param('action')))switch($action){
		case 'apply_update':
			$param['action'] = 'edit';
			$param['row'] = param('login');
			break;
		case 'apply_add':
			$param['action'] = 'edit';
			$param['row'] = $this->getRow();
			break;
	}
	if($mess) $param['mess'] = $mess;
	header('Location: '.ap::getUrl($param));
	die;
}
function getForm($action){
	$xml = $this->getDataXML();
	$form_element = null;
	switch($action){
		case 'update':
		case 'apply_update':
		case 'edit':
			$form_element = $xml->getElementById('user_form_edit');
			break;
		case 'new':
		case 'add':
		case 'apply_add':
		default:
			$form_element = $xml->getElementById('user_form_add');
			break;
	}
	return $form_element ? new form($form_element) : null;
}
function getList($users){
	$xml = $this->getDataXML();
	if($list_element = $xml->getElementById('user_list')){
		$rl = new rowlist($list_element,$users->getNum(),param('page'));
		$s = $rl->getStartIndex();
		$f = $rl->getFinishIndex();
		foreach($users as $i => $usr){
			if($i<$s) continue;
			elseif($i>$f) break;
			$rl->addRow($usr->getLogin(),array(
				'sort'=>$i+1,
				'login'=>$usr->getLogin(),
				'name'=>$usr->getName(),
				'active'=>$usr->getDisabled()
			));
		}
		$rl->setFormAction(preg_replace('/&?mess=[\w_]*/','',$_SERVER['REQUEST_URI']));
		return $rl;
	}
}
function run(){
	global $_out;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		$action = param('action');
		$users = new users();
		$form = $this->getForm($action);
		$row = $this->getRow();
		switch($action){
			case 'active':
				if($row && $users->userExists($row)){
					$usr = $users->getUser($row);
					$usr->disable(param('active')=='on');
					if(param('ajax'))
						ap::ajaxResponse($usr->getDisabled() ? 'off' : 'on');
					else $this->redirect('active_ok');
				}
				break;
			case 'move':
				if($row && $users->userExists($row) && ($pos = param('pos'))>0){
					$users->moveUser($users->getUser($row),$pos);
					$this->redirect('move_ok');
				}else $this->redirect('move_fail');
				break;
			case 'delete':
				if($row && $users->userExists($row)){
					$users->removeUser(param('row'));
					$this->redirect('delete_ok');
				}else $this->redirect('delete_fail');
				break;
			case 'update':
			case 'apply_update':
				if($row && $users->userExists($row)){
					$pos = $users->getPos($users->getUser($row))+1;
					$form->replaceURI(array(
						'POSITION'=>$pos
						,'PATH_SITE' => ABS_PATH_SITE
						,'PATH_USERS' => ABS_PATH_USERS
					));
					$form->save($_REQUEST);
					$this->redirect('update_ok');
				}else $this->redirect('update_fail');
				break;
			case 'add':
			case 'apply_add':
				if($row && !$users->userExists($row)){
					$form->replaceURI(array(
						'PATH_SITE' => ABS_PATH_SITE
						,'PATH_USERS' => ABS_PATH_USERS
					));
					$form->save($_REQUEST);
					$this->redirect('add_ok');
				}else $this->redirect('add_fail');
				break;
			case 'edit':
				$pos = $users->getPos($users->getUser($row))+1;
				$form->replaceURI(array(
					'POSITION'=>$pos
					,'PATH_SITE' => ABS_PATH_SITE
					,'PATH_USERS' => ABS_PATH_USERS
				));
				$form->load();
			case 'new':
				$_out->elementIncludeTo($form->getRootElement(),'/page/section');
				break;
			default:
				if($rl = $this->getList($users))
					$_out->elementIncludeTo($rl->getRootElement(),'/page/section');
		}
	}
}
function getDataXML(){
	if(is_file($path = PATH_MODULE.$this->getName().'/data.xml') //путь к папке модуля (меняется, если наследуется другим модулем)
		|| is_file($path = PATH_MODULE.__CLASS__.'/data.xml') //всегда путь к папке этого модуля
	) return new xml($path);
}
}