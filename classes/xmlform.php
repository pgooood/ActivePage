<?php
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
	$arrEmptyOrFlags = array();
	$res = $this->query('.//field[@check or @type="email"]');
	foreach($res as $field){
		$name = $field->getAttribute('name');
		$val = $this->response($name);
		if(preg_match('/empty-or-([^\s"]+)/',$field->getAttribute('check'),$m)
			&& ($field2 = $this->getField($m[1]))
		){
			if(!in_array($m[1],$arrEmptyOrFlags)){
				$arrEmptyOrFlags[] = $field->getAttribute('name');
				if($this->validateFieldValue($field) && $this->validateFieldValue($field2))
					$message[$name] = 'Поле "'.$field->getAttribute('label').'" или "'.$field2->getAttribute('label').'" должно быть заполнено';
			}
			continue;
		}
		if($err = $this->validateFieldValue($field))
			$message[$name] = $err;
	}
	if(($captcha = $this->getCaptcha()) && !$captcha->check())
		$message[$captcha->getParamName()] = 'Результат выражения с картинки введен неверно';
	return $message;
}

function validateFieldValue($field){
	$name = $field->getAttribute('name');
	$val = $this->response($name);
	$isEmptyCheck = strstr($field->getAttribute('check'),'empty') && !$val;
	$error = null;
	switch($field->getAttribute('type')){
		case 'password_confirm':
			if($isEmptyCheck)
				$error = 'Поле "'.$field->getAttribute('label').'" не заполнено.';
			if(strlen($val)<6)
				$error = 'Минимальная длина пароля <strong>6</strong> символов.';
			if($val != $this->response($name.'_confirm'))
				$error = 'Введенные пароли не совпадают';
			break;
		case 'checkbox':
		case 'radio':
			if($isEmptyCheck)
				$error = 'Поле "'.$field->getAttribute('label').'" не отмечено';
			break;
		case 'email':
			if($val && !mymail::isEmail($val))
				$error = 'Адрес электронной почты в поле "'.$field->getAttribute('label').'" введен неверно';
			break;
		case 'recaptcha':
			if($val){
				$json = file_get_contents(
					'https://www.google.com/recaptcha/api/siteverify'
					,false
					,stream_context_create(array(
						'http' => array(
							'method'  => 'POST',
							'header'  => 'Content-type: application/x-www-form-urlencoded',
							'content' => http_build_query(array(
								'secret' => $field->getAttribute('secret')
								,'response' => $val
							))
						)
					))
				);
				if($json && ($resp = json_decode($json))){
					if(empty($resp->success))
						$this->err('Капча введена неверно');
				}else
					$this->err('Ошибка, связь с сервисом не удалась');
			}else
				$this->err('Капча введена неверно');
			break;
		default:
			if(strstr($field->getAttribute('check'),'empty') && !$val)
				$error = 'Поле "'.$field->getAttribute('label').'" не заполнено';
	}
	return $error;
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