<?
class apTemplateManager extends module{
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
		case 'update':
		case 'apply_update':
			$mess = 'Шаблон успешно сохранен';
			break;
		case 'add':
		case 'apply_add':
			$mess = 'Шаблон успешно добавлен';
			break;
		case 'apply_def_tpl':
			$mess = 'Главный шаблон успешно изменен';
			break;
		default:
			if(preg_match("/^delete_([0-9]{1,3})$/",$_SESSION['apMess'][$this->getMessSessionName()],$res)){
				$c = $res[1] == 1 ? 1 : 0;
				$mess = 'Удаление выполнено успешно. Обработано: '.$res[1].' шабл.';
			};
			break;
	}
	$_SESSION['apMess'] = array();
	return $mess;
}
function run(){
	global $_out,$_struct;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		
		// управление текущими шаблонами
		$templates = $this->getTemplate();
		if(is_object($templates)){
			$num_templates = $templates->getNum()+1;
		}else{
			$num_templates = 1;
		}
		// все шаблоны доступные для подключения
		$attay_all_template = $this->getAllTemplates(false);
		
		// объект в массив
		$array_all_template_by_sec = array();
		
		if($templates){
			foreach($templates as $template){
				$array_all_template_by_sec[] = $template->getAttribute('id');
			}
		}
		// разность
		$array_diff = array_diff($attay_all_template,$array_all_template_by_sec);
		
		$action = param('action');
		$form = $this->getForm($action);
		
		$form_def_tpl = $this->getForm('edit_def_tpl');
		$form_def_tpl->replaceURI(array('ID' => $this->getIdSection()));
		
		switch($action){
			case 'delete':
				if($row = param('row')){
					if(!is_array($row)) $row = array($row);
					$row = array_reverse($row);
					$count = 0;
					foreach($row as $v){
						if($templates->getById($v)){
							$templates->remove($templates->getById($v));
							$count++;
						}
					}
					if($count){
						$templates->getXML()->save();
					}
					$this->setMessage($action.'_'.$count);
				}
				$this->redirect($action);
				break;
			case 'update':
			case 'apply_update':
				$form->replaceURI(array(
					'ID' => $this->getIdSection()
					,'TEMLATEID' => param('row')
					,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
					,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
				));
				$form->save($_REQUEST);
				$this->setMessage($action);
				$this->redirect($action,param('row'));
				break;
			case 'add':
			case 'apply_add':
				if($id = ap::getClientSection($this->getIdSection())->getTemplate()->getId()){
					$form->replaceURI(array(
						'ID' => $this->getIdSection()
						,'TEMLATEID' => $id
						,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
						,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
					));
					$form->save($_REQUEST);
					$tpl = param('tpl');
					$this->setMessage($action);
					$this->redirect($action,$tpl['id']);
				}
				break;
			case 'edit':
				if($id = ap::getClientSection($this->getIdSection())->getTemplate()->getId()){
					$form->replaceURI(array(
						'ID' => $this->getIdSection()
						,'TEMLATEID' => param('row')
						,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
						,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
					));
					$select = $form->getField('id_template');
					if($template = $templates->getById(param('row'))){
						$array_diff[] = $template->getAttribute('id');
					};
					foreach($array_diff as $item){
						$select->addOption($item,$item);
					}
					$form->load();
					$_out->elementIncludeTo($form->getRootElement(),'/page/section');
				}else{
					$this->redirect();
				}
				break;
			case 'new':
				if($id = ap::getClientSection($this->getIdSection())->getTemplate()->getId()){
					$select = $form->getField('tpl[id]');
					foreach($array_diff as $item){
						$select->addOption($item,$item);
					}
					$_out->elementIncludeTo($form->getRootElement(),'/page/section');
				}else{
					$this->redirect();
				}
				break;
			case 'apply_def_tpl':
				$form_def_tpl->save($_REQUEST);
				$this->setMessage($action);
				$this->redirect($action);
				break;
			default:
				if($res = $this->getList(array('count'=>count($array_diff)))){
					$select_def_tpl = $form_def_tpl->getField('id_template');
					foreach($attay_all_template as $item){
						$select_def_tpl->addOption($item,$item);
					}					
					// форма редактирования по умолчнию
					$form_def_tpl->load();
					if(!$select_def_tpl->getValue()){
						$select_def_tpl->setValue('default');
					}
					
					$_out->elementIncludeTo($form_def_tpl->getRootElement(),'/page/section');
					
					// подключаем rowlist
					$_out->elementIncludeTo($res,'/page/section');
				}
		}
	}
}
function redirect($action = null,$id = null){
	$param = array();
	switch($action){
		case 'apply_add':
		case 'apply_update':
			if($id){
				$param['action'] = 'edit';
				$param['row'] = $id;
			}
			break;
	}
	header('Location: '.ap::getUrl($param));
	die;
}


function getIdSection(){
	return substr($this->getSection()->getId(),3);
}

function getForm($action){
	$form_element = null;
	switch($action){
		case 'update':
		case 'apply_update':
		case 'edit':
			$xml = new xml(PATH_MODULE.__CLASS__.'/form/edit.xml');
			break;
		case 'new':
		case 'add':
		case 'apply_add':
			$xml = new xml(PATH_MODULE.__CLASS__.'/form/add.xml');
			break;
		case 'edit_def_tpl':
			$xml = new xml(PATH_MODULE.__CLASS__.'/form/edit_def_tpl.xml');
			break;
	}
	if($xml && $xml->de()){
		$form_element = $xml->de();
	}
	return $form_element ? new form($form_element) : null;
}

function getList($param){
	$rl_xml = new xml(PATH_MODULE.__CLASS__.'/form/rowlist.xml');
	
	if($list_element = $rl_xml->de()){		
		$templates = $this->getTemplate();
				
		$rl = new rowlist($list_element,count($templates),param('page'));
		$headers = $rl->getHeaders();
		
		if(!$param['count']){
			$list_elem = $rl->getRootElement();
			$list_elem->removeAttribute('add');
		}
		
		if($templates){
			foreach($templates as $i => $template){
				if($i>=$rl->getStartIndex() && $i<=$rl->getFinishIndex()){
					$rl->addRow($template->getAttribute('id'),array('id_template'=>$template->getAttribute('id')));
				}
			}
		}
		return $rl->getRootElement();
	}
}
function getTemplate(){
	$sec = ap::getClientSection($this->getIdSection());
	return $sec->getTemplateList();
}
function getAllTemplates(){
	$template = array();
	if($dir = scandir(PATH_TPL_CLIENT)){
		foreach($dir as $entry){
			if($entry != "." && $entry != ".." && preg_match('/^(.+)\.xsl$/',$entry,$match)){
				$path = PATH_TPL_CLIENT.$entry;
				if(file_exists($path)){
					$template[] = $match[1];
				}
			}
		}
	}
	return $template;
}

}