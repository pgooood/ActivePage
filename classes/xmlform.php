<?
class xmlform{
private $e;
function __construct(DOMElement $e){
	$this->setElement($e);
	$this->setAction($_SERVER['REQUEST_URI']);
}
function setAction($v){
	$this->getElement()->setAttribute('action',$v);
}
function setElement(DOMElement $e){
	$this->e = $e;
}
function getElement(){
	return $this->e;
}
function query($v){
	$xml = new xml($this->getElement());
	return $xml->query($v,$this->getElement());
}
function evaluate($v){
	$xml = new xml($this->getElement());
	return $xml->evaluate($v,$this->getElement());
}
function getField($name){
	return $this->query('.//*[(name()="field" or name()="param") and @name="'.htmlspecialchars($name).'"]')->item(0);
}
function sent(){
	return param('action') == $this->evaluate('string(.//param[@name = "action"]/@value)');
}
function getCaptcha(){
	if($name = $this->evaluate('string(.//field[@type = "captcha"]/@name)')){
		$captcha = new captcha();
		$captcha->setParamName($name);
		$captcha->setLanguage('ru');
		return $captcha;
	}
}
function message($v){
	$xml = new xml($this->getElement());
	return $this->getElement()->appendChild($xml->createElement('message',null,$v));
}
function check(){
	$message = array();
	$res = $this->query('.//field[@check or @type="email"]');
	foreach($res as $field){
		$name = $field->getAttribute('name');
		$val = $this->response($name);
		$isEmptyCheck = strstr($field->getAttribute('check'),'empty') && !$val;
		switch($field->getAttribute('type')){
			case 'password_confirm':
				if($isEmptyCheck)
					$message[$name] = 'Поле "'.$field->getAttribute('label').'" не заполнено.';
				if(strlen($val)<6)
					$message[$name] = 'Минимальная длина пароля <strong>6</strong> символов.';
				if($val != $this->response($name.'_confirm'))
					$message[$name] = 'Введенные пароли не совпадают';
				break;
			case 'checkbox':
			case 'radio':
				if($isEmptyCheck)
					$message[$name] = 'Поле "'.$field->getAttribute('label').'" не отмечено';
				break;
			case 'email':
				if($val && !mymail::isEmail($val))
					$message[$name] = 'Адрес электронной почты в поле "'.$field->getAttribute('label').'" введен неверно';
				break;
			case 'delivery':
				if($isEmptyCheck)
					$message[$name] = 'Поле "'.$field->getAttribute('label').'" не отмечено';
				if(mb_stristr($val,'Транспортная компания')!==false){
					$val2 = $this->response($name.'_company');
					if(!$val2) $message[$name] = 'Транспортная компания не выбрана';
				}
				if(mb_stristr($val,'Самовывоз')===false && !$this->response('delivery_address')){
					$message[$name] = 'Не указан адрес доставки';
				}
				break;
			default:
				if(strstr($field->getAttribute('check'),'empty') && !$val)
					$message[$name] = 'Поле "'.$field->getAttribute('label').'" не заполнено';
		}
	}
	if(($captcha = $this->getCaptcha()) && !$captcha->check())
		$message[$captcha->getParamName()] = 'Результат выражения с картинки введен неверно';
	return $message;
}
function fill(){
	$xml = new xml($this->getElement());
	$res = $xml->query('.//field',$this->getElement());
	foreach($res as $field)
		$this->setValue($field,$this->response($field->getAttribute('name')));
}
function setValue($e,$val){
	if(is_string($e))
		$e = $this->getField($e);
	if(!(is_object($e) && $e instanceof DOMElement && $e->hasAttribute('type'))) return false;
	switch($e->getAttribute('type')){
		case 'radio':
			$xml = new xml($e);
			$opts = $xml->query('option',$e);
			foreach($opts as $opt){
				if($val == ($opt->hasAttribute('value') ? $opt->getAttribute('value') : $xml->evaluate('string(text())',$opt))){
					$opt->setAttribute('checked','checked');
					break;
				};
			}
			break;
		case 'checkbox':
			$fieldValue = $e->hasAttribute('value') ? $e->getAttribute('value') : 1;
			if(!is_array($val)) $val = array($val);
			foreach($val as $v) if($v == $fieldValue)
				$e->setAttribute('checked','checked');
			break;
		case 'checkboxgroup':
			$xml = new xml($e);
			$opts = $xml->query('option',$e);
			if(!is_array($val)) $val = array($val);
			foreach($opts as $j => $opt){
				if(in_array(($opt->hasAttribute('value') ? $opt->getAttribute('value') : $xml->evaluate('string(text())',$opt)),$val))
					$opt->setAttribute('checked','checked');
			}
			break;
		case 'select':
			$e->setAttribute('value',$val);
			break;
		default:
			xml::setElementText($e,$val);
	}
	return true;
}
static function response($fieldName){
	$value = param($fieldName);
	if(preg_match('/([^\[]+)(\[.+)/',$fieldName,$m)){
		$fieldName = $m[1];
		$value = param($fieldName);
		while(is_array($value)
			&& isset($m[2])
			&& preg_match('/\[([^\]]+)\](.*)/',$m[2],$m)
			&& isset($value[$m[1]])
		) $value = $value[$m[1]];
	}
	return $value;
}
}