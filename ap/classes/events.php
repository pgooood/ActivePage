<?
class events extends taglist{
function __construct($uri){
	$xml = new xml($uri,'events');
	parent::__construct($xml->de(),'event');
	$this->xml = $xml;
}
function getEvent($name){
	if($e = $this->getById($name,'name'))
		return new taglist($e,'module');
}
function addEvent($name){
	if(!$this->getEvent($name))
		$this->append(array('name' => $name));
}
function addListener($event,module $module,$params = null){
	if($e = $this->getEvent($event)){
		$l = $e->append(array('module' => $module->getId()));
		if($sec = $module->getSection())
			$l->setAttribute('section',$sec->getId());
		if(is_array($params)){
			foreach($params as $name => $value){
				if($name && $value && $name!='section')
					$l->setAttribute($name,$value);
			}
		}
	}
	//$this->getXML()->save('temp2.xml');
}
function get($name){
	return $this->getById($name,'name');
}
function happen($name){
	global $_site,$_struct;
	if($event = $this->get($name)){
		$res = $this->xml->query('module',$event);
		foreach($res as $e){
			if(!($mId = $e->getAttribute('module')))
				continue;
			if($sId = $e->getAttribute('section')){
				if(($sec = $_struct->getSection($sId))
					&& ($mod = $sec->getModules()->getById($mId))
					&& method_exists($mod,$method = 'on'.$name)
				){
					$params = array();
					foreach($e->attributes as $attr){
						switch($attr->name){
							case 'name':
							case 'module':
							case 'section': continue;
							default: $params[$attr->name] = $attr->value;
						}
					}
					if(count($params))
						call_user_func(array(&$mod,$method),$params);
					else
						call_user_func(array(&$mod,$method));
				}
			}elseif(($mod = $_site->getModules()->getById($mId))
				&& method_exists($mod,$method = 'on'.$name)
			){
				call_user_func(array(&$mod,$method));
			}
		}
	}
}
}
?>