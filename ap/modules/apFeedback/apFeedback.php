<?
/**
* 
**/
class apFeedback extends module{
private $xml;
private $sec;
private $forms;
private $tl;
private $checks;
function __construct(DOMElement $e,structure $struct){
	parent::__construct($e,$struct);
}
function getRow(){
	return param('row');
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
				break;
			case 'edit':
				$param['action'] = 'new';
		}
	}
	if($page = param('page')) $param['page'] = $page;
	$this->setMessage($mess);
	header('Location: '.ap::getUrl($param));
	die;
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
		case 'add_ok':
			$mess = 'Поле успешно добавлено'; break;
		case 'add_fail':
			$mess = 'При добавлении поля произошла ошибка в заполнении аттрибутов'; break;
		case 'save_ok':
			$mess = 'Данные успешно сохранены'; break;
		case 'delete_ok':
			$mess = 'Поле удалено'; break;
		case 'delete_fail':
			$mess = 'Ошибка. Поле не было удалено'; break;
		case 'move_ok':
			$mess = 'Перемещение успешно выполнено.'; break;
		case 'move_fail':
			$mess = 'Ошибка. Перемещение не может быть выполнено.'; break;
		case 'update_ok':
			$mess = 'Обновление успешно выполнено.'; break;
		case 'update_fail':
			$mess = 'Ошибка. Обновление не может быть выполнено.'; break;
	}
	$_SESSION['apMess'] = array();
	return $mess;
}
function run(){
	global $_out,$_sec;
	if(ap::isCurrentModule($this)){
		ap::addMessage($this->getMessage());
		$this->xml		= $this->getSection()->getXML();
		$this->sec		= ap::getClientSection($_sec->getId())->getModules()->getXML();
		$action			= param('action');
		$this->forms	= $this->getForm($action);
		$this->tl		= $this->getTagList();
		$this->tl->setKeyAttribute('name');
		$this->checks	= array(
			 'text'		=>'empty'
			,'email'	=>'empty email'
			,'password' =>'empty'
			,'textarea'	=>'empty'
		);
		
		switch($action){
			case 'delete':
				if($this->onDelete($action)){
					$this->redirect('delete_ok');
				}else $this->redirect('delete_fail');
				break;
			case "move":				
				if(($row = $this->getRow())
					&& (($pos = param('pos'))>0)
					&& ($e = $this->tl->getById($row))
				){
					$this->tl->move($e,$pos);
					$this->tl->getXML()->save();
					$this->redirect('move_ok');
				}else $this->redirect('move_fail');
				break;
			case 'update':
			case 'apply_update':
				if($this->onUpdate($action))
					$this->redirect('update_ok');
				else $this->redirect('update_fail');
				break;
			case 'edit':
				$this->onEdit($action);
				break;
			case 'add':
			case 'apply_add':
				if($this->onAdd())
					$this->redirect('add_ok');
				else $this->redirect('add_fail');
				break;
			case 'new':
				$this->onNew($action);
				break;
			default:
				if($rl = $this->getList()) $_out->addSectionContent($rl->getRootElement());
				break;
		}
	}
}
function fixedFields($form){
	global $_sec;
	$clientSection = ap::getClientSection($_sec->getId());
	$md = $clientSection->getXML()->getElementById($this->getId());
	if($clientSection->getXML()->evaluate('string(./form[1]/@dbSave)',$md)
		&& ($dbConnect = $clientSection->getXML()->evaluate('string(./form[1]/@dbConnect)',$md))
		&& ($dbTable = $clientSection->getXML()->evaluate('string(./form[1]/@dbTable)',$md))
		&& ($ff = $form->appendField(formSelect::create('uri', 'Имя поля в таблице базы данных для сохранения:', '/@uri')))
	) {
		$ff->addOption('','...');
		$mysql = new mysql($dbConnect);
		$q = 'SHOW COLUMNS FROM '.$mysql->getTableName($dbTable);
		$rs = $mysql->query($q);
		while($res = $mysql->fetch($rs)) {
			if(!$res['Extra']) $ff->addOption($res['Field'],$res['Field']);
		}

	}
}
function onDelete(){
	if($row = $this->getRow()){
		if(!is_array($row)) $row = array($row);
		$counter = 0;
		foreach($row as $id){
			if($id && $this->tl
				&& ($e = $this->tl->getById($id))
			){
				$counter++;
				$this->tl->remove($e);
			}
		}
		if($counter && $counter==count($row)){
			$this->tl->getXML()->save();
			return true;
		}
	}
}
function onUpdate($action){
	global $_sec;
	if(($row = $this->getRow())
		&& $this->tl->getById($row)
	){
		$form = $this->getForm($action);
		$this->fixedFields($form);
		$form->replaceURI(array(
			'ID' => $_sec->getId(),
			'MD' => $this->getId(),
			'FID'=>$row
		));
		$_REQUEST['size'] = $_REQUEST['size'] ? $_REQUEST['size'] : 40;
		if($_REQUEST['type'] != 'textarea') $_REQUEST['rows'] = null;
		else $_REQUEST['rows'] = $_REQUEST['rows']? $_REQUEST['rows']:6;
		$_REQUEST['check'] = $_REQUEST['check']?@$this->checks[$_REQUEST['type']] : null;
		$form->save($_REQUEST);
	}
	return $row;
}
function onEdit($action){
	global $_out,$_sec;
	if($row = $this->getRow()){
		$form = $this->getForm($action);
		$this->fixedFields($form);
		$form->replaceURI(array(
			'ID' => $_sec->getId(),
			'MD' => $this->getId(),
			'FID'=>$row
		));
		$form->load();
		if(($ff = $form->getField('size')) && ($ff->getValue() == '')) $ff->setValue(40);
		$_out->elementIncludeTo($form->getRootElement(),'/page/section');
	}
}
function onAdd(){
	if($this->tl){
		$values = array(
			 'type'		=>$_REQUEST['type']
			,'name'		=>$this->tl->generateId('f')
			,'label'	=>$_REQUEST['label']
			,'mail'		=>$_REQUEST['mail'] ? 1 : null
			,'check'	=>$_REQUEST['check']? @$this->checks[$_REQUEST['type']] : null
			,'size'		=>$_REQUEST['size']
		);
		if($_REQUEST['type'] == 'textarea' && $_REQUEST['rows']) $values['rows'] = $_REQUEST['rows'] ? $_REQUEST['rows'] : 6;
		if($_REQUEST['uri']) $values['uri'] = $_REQUEST['uri'];
		$_REQUEST['row'] = $values['name'];
		$e = $this->tl->append($values);
		$this->tl->move($e,intval($this->tl->getNum()-1));
		$this->tl->getXML()->save();
		return true;
	}
	return false;
}
function onNew($action){
	global $_out;
	$form = $this->getForm($action);
	$this->fixedFields($form);
	if($ff = $form->getField('size')) $ff->setValue(40);
	$_out->elementIncludeTo($form->getRootElement(),'/page/section');
}
function getForm($action){
	$fe = null;
	if(!is_array($this->forms)) $this->forms = array();
	if(isset($this->forms[$action])) return $this->forms[$action];
	switch($action){
		case 'update':
		case 'apply_update':
		case 'edit':
			$fe = $this->xml->getElementById('feedback_editForm');
			break;
		case 'new':
		case 'add':
		case 'apply_add':
		default:
			$fe = $this->xml->getElementById('feedback_addForm');
			break;
	}
	
	if($fe){
		$this->forms[$action] = new form($fe);
		return $this->forms[$action];
	}
}
function getTagList(){
	global $_sec;
	if($_sec->getId() && $this->getId() 
		&& ($_cs = ap::getClientSection($_sec->getId()))
	){
		$m = $_cs->getModules()->getById($this->getId());
		return new taglist($m->query('form')->item(0),'field');
	}else throw new Exception('Not fount client section',EXCEPTION_XML);
}
function getList(){
	$types = array(
		 'text'=>'Текстовое однострочное поле'
		,'email'=>'Электронный адрес'
		,'password'=>'Пароль'
		,'textarea'=>'Многострочное текстовое поле'
	);
	if($e = $this->xml->getElementById("feedback_list_fields")){
		//$q = './field[not(@type = "captcha")]';
		$q = './field[(@type = "text") or (@type = "textarea") or (@type = "email") or (@type = "password")]';
		$from = $this->sec->getElementById("feedback_form");

		$list = $this->sec->query($q,$from);
		$rl = new rowlist($e,$list->length,param('page'));
		$s = $rl->getStartIndex();
		$f = $rl->getFinishIndex();
		foreach($list as $i => $row){
			if($i<$s) continue;
			elseif($i>$f) break;
			$rl->addRow($row->getAttribute('name'),array(
					'sort'	=>$i+1,
					'type'	=>$types[$row->getAttribute('type')],
					'label'	=>$row->getAttribute('label')
				));
		}
		$rl->setFormAction(preg_replace('/&?mess=[\w_]*/','',$_SERVER['REQUEST_URI']));
		return $rl;
	}

}
function install(){
	if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/classes/feedback.php')) 
		copy($_SERVER['DOCUMENT_ROOT'].'/ap/modules/'.get_class($this).'/client/feedback.php', $_SERVER['DOCUMENT_ROOT'].'/classes/feedback.php');
	if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/xml/templates/email_feedback.xsl')) 
		copy($_SERVER['DOCUMENT_ROOT'].'/ap/modules/'.get_class($this).'/client/email_feedback.xsl', $_SERVER['DOCUMENT_ROOT'].'/xml/templates/email_feedback.xsl');
	if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/xml/templates/email_feedback_user.xsl')) 
		copy($_SERVER['DOCUMENT_ROOT'].'/ap/modules/'.get_class($this).'/client/email_feedback_user.xsl', $_SERVER['DOCUMENT_ROOT'].'/xml/templates/email_feedback_user.xsl');
	
	$xml_data	= new xml(PATH_MODULE.get_class($this).'/data.xml');
	$this->xml	= $this->getSection()->getXML();
	$ar = array('feedback_addForm','feedback_editForm','feedback_list_fields');
	foreach($ar as $id){
		$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
		if($e && !$this->xml->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
			$this->xml->elementIncludeTo($e,$this->getRootElement());
	}
	$this->xml->save();
	
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if(!$modules->getById($this->getId())){
			$modules->add('feedback',$this->getTitle(),$this->getId());
			$e = $xml_data->query('//*[@id="feedback_form"]')->item(0);
			$m = $modules->getXML();
			if($e && !$this->xml->evaluate('count(./*[@id="feedback_form"])',$this->getRootElement()))
				$m->elementIncludeTo($e,$m->getElementById('feedback','name'));
			$m->save();
		}
		return true;
	}
}
function uninstall(){
	if($sec = ap::getClientSection($this->getSection()->getId())){
		$modules = $sec->getModules();
		if($modules->remove($this->getId()))
			$modules->getXML()->save();
		return true;
	}
}
function settings($action){
	global $_out,$_site;
	$xml = new xml(PATH_MODULE.get_class($this).'/data.xml');
	if($e = $xml->getElementById('feedback_form_settings')){
		$form = new form($e);
		$form->replaceURI(array(
			'MD'=>$this->getId(),
			'ID'=>$this->getSection()->getId()
		));
		switch($action){
			case 'update':
			case 'apply_update':
				$form->save($_REQUEST);
				return;
		}
		
		//fill select from connect
		$select_connects = $form->getField('form__db_name_connect');
		$mysqlConnects = $_site->query('//mysql/con');
		foreach ($mysqlConnects as $con){
			$select_connects->addOption($con->getAttribute('id'),$con->getAttribute('id'));
		}
		//fill selects from mail templates
		$tpls =  apTemplateManager::getAllTemplates();
		$select_tpl = $form->getField('form_email_tpl');
		$select_uTpl = $form->getField('form_email_tpl_user');
		foreach($tpls as $item){
			$select_tpl->addOption($item,$item);
			$select_uTpl->addOption($item,$item);
		}
		
		#fill tables
		$form->load(); //for database connection
		$con = $select_connects->getValue();
		$mysql = new mysql(($con? $con : null));
		$rs = $mysql->query('SHOW TABLES');
		$select_tb = $form->getField('form_db_name_table');
		while($res = $mysql->fetchArray($rs))
			$select_tb->addOption($res[0], $res[0]);
		
		$form->load();
		$_out->addSectionContent($form->getRootElement());
	}
}
}
?>