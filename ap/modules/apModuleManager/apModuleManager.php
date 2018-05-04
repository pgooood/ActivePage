<?
class apModuleManager extends module{
function run(){
	global $_out;
	if(ap::isCurrentModule($this)){
		$action = param('action');
		// управление текущими модулями
		$modules = $this->getModules();
		// все модули доступные для подключения
		$all_modules = $this->getModuleList();
		
		$form = $this->getForm($action);
		switch($action){
			case 'active':
				if(($row = param('row'))
					&& ($sec = $this->getDataSection())
					&& ($modules = $sec->getModules())
					&& ($module = $modules->getById($row))
					&& ($e = $module->getRootElement())
				){
					if(param('active')=='on') $e->setAttribute('readonly','readonly');
					elseif($e->hasAttribute('readonly')) $e->removeAttribute('readonly');
					$modules->getXML()->save();
					$state = !(param('active')=='on');
					if(param('ajax'))
						ap::ajaxResponse($state ? 'on' : 'off');
				}
				break;
			case 'move':
				if(($row = param('row')) && ($pos = param('pos'))){
					$modules->move($row,$pos);
					$modules->getXML()->save();
				}
				$this->redirect($action);
				break;
			case 'delete':
				if($row = param('row')){
					apModuleManager::removeModule($this->getIdSection(),$row);
				}
				$this->redirect($action);
				break;
			case 'add':
			case 'apply_add':
				if($m = apModuleManager::addModule($this->getIdSection(),param('name'),param('title'),param('readonly'))){
					$this->redirect($action,$m->getId());
				}else{
					throw new Exception('Error add module "'.$_REQUEST['name'].'"');
				}
				break;
			case 'update':
			case 'apply_update':
			case 'edit':
				if(($row = param('row'))
					&& ($sec = $this->getDataSection())
					&& ($modules = $sec->getModules())
					&& ($module = $modules->getById($row))
					&& method_exists($module,'settings')
				){
					$module->settings($action);
				}
				$this->redirect($action,$row);
				break;
			case 'new':
				$m = $modules->add('tests_module');
				$form->replaceURI(array(
					'ID' => $this->getIdSection()
					,'MODULEID' => $m->getId()
					,'PATH_DATA_FILE_CLIENT' => ABS_PATH_DATA_CLIENT.ap::id($this->getSection()->getId()).'.xml'
					,'PATH_DATA_FILE_AP' => ABS_PATH_DATA_AP.ap::id($this->getSection()->getId()).'.xml'
				));
				$select = $form->getField('name');
				foreach($all_modules as $key => $module){
					$select->addOption($key,$module['name']);
				}
				$_out->elementIncludeTo($form->getRootElement(),'/page/section');
				break;
			default:
			if($res = $this->getList())
				$_out->addSectionContent($res);
		}
	}
}
static function getModuleList(){
	$ar = array();
	if($dir = scandir(PATH_MODULE)){
		foreach($dir as $entry){
			if($entry != "."
				&& $entry != ".."
				&& is_dir($path = PATH_MODULE.$entry)
				&& file_exists($path.= '/info.xml')
			){
				$ar[$entry] = array();
				$xml = new xml($path);
				$res = $xml->query('/*/*');
				foreach($res as $e)
					if($e instanceof DOMElement)
						$ar[$entry][$e->tagName] = xml::getElementText($e);
			}
		}
	}
	return $ar;
}
static function getModuleInfo($name){
	$ml = apModuleManager::getModuleList();
	foreach($ml as $i => $info)if($i == $name) return $info;
}
static function addModule($sec_id,$module = null,$title = "",$readonly = false){
	global $_struct;
	if(!$module) $module = 'apContent';
	$sec_id = ap::id($sec_id);
	if(($info = apModuleManager::getModuleInfo($module))
		&& ($sec = $_struct->getSection($sec_id))
		&& ($modules = $sec->getModules())
		&& ($m = $modules->add($module,($title ? $title : @$info['name'])))
	){
		if($readonly) $m->getRootElement()->setAttribute('readonly','readonly');
		if(method_exists($m,'install')){
			if(!$m->install())
				apModuleManager::removeModule($sec_id,$m->getId());
		}
		$modules->getXML()->save();
		return $m;
	}
	return false;
}
static function removeModule($sec_id,$row){
	global $_struct;
	if(($sec = $_struct->getSection($sec_id)) && ($modules = $sec->getModules())){
		if(!is_array($row)) $row = array($row);
		foreach($row as $v){
			if(is_object($module_obj = $modules->getById($v))){
				if(method_exists($module_obj,'uninstall')){
					if(!$module_obj->uninstall())
						throw new Exception('Error uninstall module "'.$v.'"');
				}
				$modules->remove(htmlspecialchars($v));
			}
		}
		$modules->getXML()->save();
	}
}
function redirect($action,$id = null){
	$param = array();
	switch($action){
		case 'edit':return;
		case 'add':break;
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
	return ap::id($this->getSection()->getId());
}
function getDataSection(){
	global $_struct;
	return $_struct->getSection($this->getIdSection());
}

function getForm($action){
	$xml = new xml(PATH_MODULE.$this->getName().'/data.xml');
	if($e = $xml->query('/data/form[@id="form_add"]')->item(0))
		return new form($e);
}

function getList($param = false){
	$xml = new xml(PATH_MODULE.$this->getName().'/data.xml');
	if($e = $xml->query('/data/rowlist')->item(0)){
		
		$modules = $this->getModuleList();
		$modules_by_this_section = $this->getModules();
		
		$rl = new rowlist($list_element,$modules_by_this_section->getNum(),param('page'));
		$headers = $rl->getHeaders();
		
		if(!$param){
			$modules_sec = array();
			
			foreach($modules_by_this_section as $key => $m){
				$modules_sec[$m->getId()] = array(
					'idmodule'	=>	$m->getId(),
					'title'		=>	$m->getTitle(),
					'name'		=>	$m->getName(),
				);
			};
			
			$modules_new = array();
			foreach($modules_sec as $key => $m){
				if(isset($modules[$m['name']])){
					$modules_new[$key] = $modules_sec[$key];
					$modules_new[$key]['name'] = $modules[$m["name"]]['name'];
					$modules_new[$key]['version'] = $modules[$m["name"]]['version'];
					$modules_new[$key]['description'] = $modules[$m["name"]]['description'];
					$modules_new[$key]['data'] = $modules[$m["name"]]['data'];
					
				}else{//модуль в разделе есть а в папке с модулями его нет, непонятно !(
					$modules_new[$key] = $m;
				}
			}
			$modules = $modules_new;
		}
		
		if(count($modules)){
			foreach($modules as $i => $module){
				if($i>=$rl->getStartIndex() && $i<=$rl->getFinishIndex()){
					$rl->addRow($i,$module);
				}
			}
		}
		
		return $rl->getRootElement();
	}
}
function getModules(){
	global $_struct;
	$sec = $_struct->getSection($this->getIdSection());
	return $sec->getModules();
}

}