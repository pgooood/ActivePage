<?php
class apTagManager extends module{
	const table = 'tags';
	private $rl;
	protected $forms;
	protected $table;
	function __construct(DOMElement $e,structure $struct){
		parent::__construct($e,$struct);
		$this->table = self::table;
	}
	function getRow(){
		if($row = param('row')){
			if(is_array($row)) foreach($row as $i => $r) $row[$i] = intval($r);
			else $row = intval($row);
		}
		return $row;
	}
	function setRow($v){
		param('row',$v);
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
				$mess = 'Тег удален'; break;
			case 'delete_fail':
				$mess = 'Ошибка, запись не удалена'; break;
			case 'update_ok':
				$mess = 'Информация успешно обновлена'; break;
			case 'update_fail':
				$mess = 'Ошибка обновления информации'; break;
			case 'add_ok':
				$mess = 'Запись добавлена'; break;
			case 'add_fail':
				$mess = 'При добавлении записи произошла ошибка'; break;
		}
		$_SESSION['apMess'] = array();
		return $mess;
	}
	function redirect($mess = null,$param = array()){
		if(!is_array($param)) $param = array();
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
		if(!is_array($this->forms)) $this->forms = array();
		if(isset($this->forms[$action])) return $this->forms[$action];
		switch($action){
			case 'update':
			case 'apply_update':
			case 'edit':
				$e = $this->query('form[@id="tag_form_edit"]')->item(0);
				break;
			default:
				$e = $this->query('form[@id="tag_form_add"]')->item(0);
				break;
		}
		if($e){
			$xml = new xml(null,null,false);
			return $this->forms[$action] = new form($xml->appendChild($xml->importNode($e)));
		}
	}
	function getReplaceValues($values = null){
		$mysql = new mysql();
		$ar = array(
				'TABLE'=>$mysql->getTableName($this->table)
				,'MODULE'=>$this->getId()
				,'SECTION'=>$this->getSection()->getId()
				,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
				,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
			);
		if(is_array($values))
			return array_merge($ar,$values);
		return $ar;
	}
	function getPreparedForm($action){
		$isCached = isset($this->forms[$action]);
		if(($form = $this->getForm($action)) && !$isCached){
			$form->replaceURI($this->getReplaceValues(array('ID'=>$this->getRow())));
		}
		return $form;
	}
	function getList(){
		if(!$this->rl){
			if($list_element = $this->query('rowlist[@id="tag_list"]')->item(0)){
				$this->rl = new mysqllist($list_element,array(
					'table' => $this->table,
					'page' => param('page')
				));
			}
		}
		return $this->rl;
	}
	function run(){
		global $_out;
		if(ap::isCurrentModule($this)){
			ap::addMessage($this->getMessage());
			switch($action = param('action')){
				case 'active':
					if($row = $this->getRow()){
						$mysql = new mysql();
						$state = !(param('active')=='on');
						$res = $mysql->update($this->table,array(
							'active' => $state ? '1' : '0'
						),'`id`='.$row);
						if(!$res) $state = !$state;
						if(param('ajax'))
							ap::ajaxResponse($state ? 'on' : 'off');
						else $this->redirect('active_'.($res ? 'ok' : 'fail'));
					}
					break;
				case 'move':
					if($this->onMove())
						$this->redirect('move_ok');
					else $this->redirect('move_fail');
					break;
				case 'delete':
					if($this->onDelete($action))
						$this->redirect('delete_ok');
					else $this->redirect('delete_fail');
					break;
				case 'update':
				case 'apply_update':
					if($this->onUpdate($action))
						$this->redirect('update_ok');
					else $this->redirect('update_fail');
					break;
				case 'add':
				case 'apply_add':
					if($this->onAdd($action))
						$this->redirect('add_ok');
					else $this->redirect('add_fail');
					break;
				case 'edit':
					if($this->onEdit($action))
						$_out->addSectionContent($this->getForm($action)->getRootElement());
					break;
				case 'new':
					if($this->onNew($action))
						$_out->addSectionContent($this->getForm($action)->getRootElement());
					break;
				default:
					if($rl = $this->getList()){
						$this->fixSortIndex();
						$rl->build();
						$_out->addSectionContent($rl->getRootElement());
					}
			}
		}
	}
	function onNew($action){
		return true;
	}
	function onEdit($action){
		if($row = $this->getRow()){
			$form = $this->getPreparedForm($action);
			$form->load($row);
			return $row;
		}
	}
	function onAdd($action){
		$mysql = new mysql();
		$form = $this->getPreparedForm($action);
		$this->setRow($row = $mysql->getNextId($this->table));
		$values = array_merge($_REQUEST,array(
			'sort' => $this->getNextSortIndex()
		));
		$form->save($values,$row);
		return $row;
	}
	function onUpdate($action){
		if($row = $this->getRow()){
			$form = $this->getPreparedForm($action);
			$form->save($_REQUEST,$row);
		}
		return $row;
	}
	function onMove(){
		if(($row = $this->getRow())
			&& ($pos = param('pos'))
			&& ($rl = $this->getList())
		){
			return $rl->moveRow($row,$pos);
		}
	}
	function onDelete($action){
		return $this->deleteRow($this->getRow());
	}
	function deleteRow($row){
		if($row	&& ($rl = $this->getList())){
			if(!is_array($row)) $row = array($row);
			foreach($row as $id)
				$rl->deleteRow($id);
			return true;
		}
	}
	function getNextSortIndex(){
		$mysql = new mysql();
		$index = 1;
		if(($rs = $mysql->query('select max(`sort`)+1 as `new_sort_index` from `'.$mysql->getTableName($this->table).'`'))
			&& ($row = $mysql->fetch($rs))
			&& $row['new_sort_index']
		) $index = $row['new_sort_index'];
		return $index;
	}
	function fixSortIndex(){
		$mysql = new mysql();
		if(($rs = $mysql->query('select `id` from `'.$mysql->getTableName($this->table).'` where NOT(`sort`>0) OR ISNULL(`sort`)'))
			&& ($index = $this->getNextSortIndex())
		){
			$arQuery = array();
			while($r = $mysql->fetch($rs))
				$arQuery[] = '('.$r['id'].','.($index++).')';
			if($arQuery)
				$mysql->query('INSERT IGNORE INTO `'.$mysql->getTableName($this->table).'` (`id`,`sort`) VALUES '.implode(',',$arQuery).' ON DUPLICATE KEY UPDATE `sort` = VALUES(`sort`)');
		}
	}
	function getDataXML(){
		if(is_file($path = PATH_MODULE.$this->getName().'/data.xml') //путь к папке модуля (меняется, если наследуется другим модулем)
			|| is_file($path = PATH_MODULE.__CLASS__.'/data.xml') //всегда путь к папке этого модуля
		) return new xml($path);
	}
	function install(){
		$mysql = new mysql();
		if(!$mysql->hasTable($this->table)){
			$mysql->query('CREATE TABLE `'.$mysql->getTableName($this->table).'` (
`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`title` varchar(127) DEFAULT NULL,
`alias` varchar(127) DEFAULT NULL,
`sort` int(10) unsigned DEFAULT NULL,
`active` tinyint(1) unsigned NOT NULL DEFAULT "1",
PRIMARY KEY (`id`),
UNIQUE KEY `iTitle` (`title`)
)');
		}

		if($xml_data = $this->getDataXML()){
			$xml_sec = $this->getSection()->getXML();
			$ar = $this->getInstallElementIdList();
			foreach($ar as $id){
				$e = $xml_data->query('//*[@id="'.$id.'"]')->item(0);
				if($e && !$xml_sec->evaluate('count(./*[@id="'.$id.'"])',$this->getRootElement()))
					$xml_sec->elementIncludeTo($e,$this->getRootElement());
			}
			$xml_sec->save();
		}

		return true;
	}
	function getInstallElementIdList(){
		return array('tag_form_edit','tag_form_add','tag_list');
	}
	function uninstall(){
		$mysql = new mysql();
		$table = $this->table;
		if($rs = $mysql->query('select * from `'.$mysql->getTableName($table).'` where `section`="'.$this->getSection()->getID().'" AND `module`="'.$this->getId().'"'))
			while($r = mysql_fetch_array($rs)) $this->deleteRow($r['id']);
		if($sec = ap::getClientSection($this->getSection()->getId())){
			$modules = $sec->getModules();
			if($modules->remove($this->getId()))
				$modules->getXML()->save();
			return true;
		}
	}
	function settings($action){
		global $_out;
		if(($xml = $this->getDataXML())
			&& ($e = $xml->getElementById('tag_form_settings'))
		){
			$form = new form($e);
			$form->replaceURI(array(
				'MODULE'=>$this->getId()
				,'SECTION'=>$this->getSection()->getId()
				,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
				,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
				,'PATH_IMAGE' => $this->pathImages.$this->getSection()->getId()
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