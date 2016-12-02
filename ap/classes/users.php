<?
class users extends taglist{
protected $keyAttribute;
function __construct(xml $xml = null){
	global $_site;
	$xml = $xml ? $xml : new xml(PATH_USERS);
	parent::__construct(users::getRootElementX($xml),'user');
	$this->keyAttribute = 'login';
	//если нет пользователей, создаем аккаунт поумолчанию
	if(!$this->getNum()){
		$ar = array(
			'login'=>'admintula',
			'pass'=>'a39d6560563dedf1edf4cd354e4960d6',
			'name'=>'Разработчик'
		);
		$this->append($ar);

		$tl = $this->getNoCacheTaglist();
		$tl->append($ar);
		$tl->getXML()->save();
	}
}
private function getNoCacheTaglist(){
	$xml = $this->getXML();
	$nocache_xml = new xml($xml->documentURI(),$xml->de()->tagName,false);
	return new taglist(users::getRootElementX($nocache_xml),'user');
}
static function getRootElementX($xml){
	$tagName = 'users';
	if($xml->de()){
		if($xml->de()->tagName == $tagName) return $xml->de();
	}else return $xml->dd()->appendChild($xml->createElement($tagName));
}
function getUser($login = null){
	if(!$login){
		if(!session_id()) session_start();
		if(isset($_SESSION['apUser'])) $login = $_SESSION['apUser'];
	}
	if($login && ($e = $this->getById($login,'login')))
		return new user($e);
}
function userExists($login){
	return (bool) $this->getById($login,'login');
}
function moveUser($val,$pos){
	$usr = null;
	if(is_object($val)){
		if($val instanceof user) $usr = $val;
	}else $usr = $this->getUser($val);
	if($usr){
		$tl = $this->getNoCacheTaglist();
		$tl->move($tl->getById($usr->getLogin(),'login'),$pos);
		$tl->getXML()->save();
		parent::move($usr->getElement(),$pos);
	}
}
function removeUser($val){
	if(!$val) return;
	if(!is_array($val)) $val = array($val);
	$tl = $this->getNoCacheTaglist();
	foreach($val as $v){
		$u = is_object($v) && $v instanceof user ? $v->getId() : $v;
		if($v && is_string($v)){
			parent::remove('@login="'.htmlspecialchars($v).'"');
			$tl->remove('@login="'.htmlspecialchars($v).'"');
		}
	}
	$tl->getXML()->save();
}
function setCurrentUser(user $usr){
	$ns = $this->getList();
	foreach($ns as $n) if($n->hasAttribute('selected')) $n->removeAttribute('selected');
	$usr->setSelected('selected');
	if(!session_id()) session_start();
	$_SESSION['apUser'] = $usr->getLogin();
}
static function getPos(user $usr){
	return parent::getPos($usr->getElement());
}
/**
* Iterator
*/
function rewind(){if($e = parent::rewind()) return new user($e);}
function current(){if($e = parent::current()) return new user($e);}
function next(){if($e = parent::next()) return new user($e);}
function valid(){return $this->current();}
}

class user{
private $e;
function __construct(DOMElement $user){
	$this->e = $user;
}
function getElement(){
	return $this->e;
}
function checkPass($pass){
	return $this->getPass()==md5($pass);
}
function disable($val){
	$xml = new xml($this->e);
	$nocache_xml = new xml($xml->documentURI(),$xml->de()->tagName,false);
	$users = new users($nocache_xml,$this->e->tagName);
	if($usr = $users->getUser($this->getLogin())){
		$val = $val ? 'disabled' : null;
		$usr->setDisabled($val);
		$nocache_xml->save();
		$this->setDisabled($val);
	}
}
function __call($m,$a){
	switch($m){
		case 'getElement':
		case 'disable':
		case 'checkPass':
			return $this->checkPass($a[0]);
		case 'getFilter':
			$ar = explode(',',$this->e->getAttribute('filter'));
			$arRes = array();
			foreach($ar as $v)
				if($v = trim($v))
					$arRes[] = $v;
			return $arRes;
		default:
			if(preg_match('/^get(\w+)$/',$m,$res) && $this->e->hasAttribute($name = strtolower($res[1])))
				return $this->e->getAttribute($name);
			elseif(preg_match('/^set(\w+)$/',$m,$res)){
				$name = strtolower($res[1]);
				if($val = $a[0]) $this->e->setAttribute($name,$val);
				elseif($this->e->hasAttribute($name)) $this->e->removeAttribute($name);
			}
	}
}
}