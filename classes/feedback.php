<?php
class feedback extends module{
protected $mess = array();

function err($mess){
	$this->mess[] = $mess;
}

function hasErrors(){
	return count($this->mess);
}
function hasCaptcha($form){
	$xml = new xml($form);
	return $xml->evaluate('count(.//field[@type="captcha"])',$form);
}
function isSent($form){
	$xml = new xml($form);
	return param('action')==$xml->evaluate('string(//param[@name = "action"]/@value)',$form);
}
function validateFieldValue($form,$field,$val){
	$pswd = null;
	$error = null;
	switch($field->getAttribute('type')){
		case 'file':
			if(strstr($field->getAttribute('check'),'empty') && (count($_FILES) == 0))
					$error = 'Файл не прикреплен';
			if($field->getAttribute('accept')
					&& (count($_FILES) > 0)
					&& $files = $_FILES[$field->getAttribute('name')]
			){
				$arrAccept = explode(',', $field->getAttribute('accept'));
				switch(is_array($files['error'])){
					case true:
						foreach ($files['error'] as $i=>$code){
							if($code == 0 && !in_array($files['type'][$i],$arrAccept)){
								$error = 'Неверный формат прикрепляемого файла. Имя файла "'.$files['name'][$i].'"';
							}
							if(($code == 0)
									&& ($maxSize = $field->getAttribute('maxFileSize'))
									&& ($files['size'][$i] >  $maxSize)){
								$error = 'Превышен допустимый размер загружаемого файла. Имя файла "'.$files['name'][$i].'"';
							}
						}
						break;
					case false:
						if(($files['error'] == 0)
								&& !in_array($files['type'],$arrAccept)){
							$error = 'Неверный формат прикрепляемого файла';
						}
						if(($files['error'] == 0)
								&& ($maxSize = $field->getAttribute('maxFileSize'))
								&& ($files['size'] >  $maxSize)){
							$error = 'Превышен допустимый размер загружаемого файла';
						}
						break;
				}
			}
			break;
		case 'password':
			if(!$pswd && ($field->getAttribute('name') == 'password')) $pswd = $val;
			if(isset($pswd) && ($field->getAttribute('name') == 'password-check') && ($pswd != $val)) $error = 'Введенные пароли не совпадают';
			if(strstr($field->getAttribute('check'),'empty') && !$val)
				$error = 'Поле "'.$field->getAttribute('label').'" не заполнено';
			break;
		case 'email':
			if($val && !mymail::isEmail($val))
				$error = 'Адрес электронной почты в поле "'.$field->getAttribute('label').'" введен неверно';
			break;
		case 'checkbox':
		case 'radio':
			if(!$val) $error = 'Поле "'.$field->getAttribute('label').'" не отмечено';
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
								//,'remoteip' => @$_SERVER['REMOTE_HOST']
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
			if($field->getAttribute('login')
				&& ($mysql = new mysql())
				&& ($mysql->query("SELECT `login` FROM `".$mysql->getTableName($form->getAttribute('dbTable'))."` WHERE `login`='".($val ? $val : null)."'", true))
			){
				$error = 'Пользователь с таким логином '.$val.' уже существует.';
			}
			if($field->getAttribute('type') != 'captcha'){
				if(strstr($field->getAttribute('check'),'empty') && !$val)
					$error = 'Поле "'.$field->getAttribute('label').'" не заполнено';
			}else{
				if($field->getAttribute('show') && strstr($field->getAttribute('check'),'empty') && !$val)
					$error = 'Поле "'.$field->getAttribute('label').'" не заполнено';
			}
	}
	return $error;
}
function check($form){
	global $_out;
	$xml = new xml($form);
	$res = $xml->query('.//field[@check]',$form);
	$arrEmptyOrFlags = array();
	foreach($res as $field){
		$val = param($field->getAttribute('name'));
		
		if(preg_match('/empty-or-([^\s"]+)/',$field->getAttribute('check'),$m)
			&& ($field2 = $xml->query('.//field[@name="'.$m[1].'"]',$form)->item(0))
		){
			if(!in_array($m[1],$arrEmptyOrFlags)){
				$arrEmptyOrFlags[] = $field->getAttribute('name');
				if($this->validateFieldValue($form,$field,$val) && $this->validateFieldValue($form,$field2,param($m[1])))
					$this->err('Поле "'.$field->getAttribute('label').'" или "'.$field2->getAttribute('label').'" должно быть заполнено');
			}
			continue;
		}
		
		if($err = $this->validateFieldValue($form,$field,$val))
			$this->err($err);
	}
	if($this->hasCaptcha($form)){
		$captcha = new captcha();
		$captcha->setLanguage($_out->getLang());
		$captcha->setParamName('captcha');
		if(!$captcha->check())
			$this->err('Результат выражения с картинки введен неверно');
	}
	return $this->hasErrors();
}
function getSentData($form){	
	global $_site;
	$xml = new xml($form);
	$mysql = new mysql();
	$res = $xml->query('.//field',$form);
	$arRes = array('xml'=>null,'mysql'=>null);
	$arRes['xml'] = new xml(null,'email',null);
	$arRes['xml']->de()->setAttribute('domain',$_site->getDomain());
	$arRes['xml']->de()->setAttribute('hash',md5($mysql->getNextId($mysql->getTableName($form->getAttribute('dbTable')))));
	
	foreach($res as $field){
		$f = array('name'=>$field->getAttribute('name'),'label'=>$field->getAttribute('label'));
		$val = param($field->getAttribute('name'));
		switch($field->getAttribute('type')){
			case 'file':
				if(!($attach = $arRes['xml']->query('./attach',$arRes['xml']->de())->item(0)))
					$attach = $arRes['xml']->de()->appendChild($arRes['xml']->createElement('attach'));
				$files = $_FILES[$field->getAttribute('name')];
				
				switch(is_array($files['error'])){
					case true:
						foreach ($files['error'] as $i=>$code){
							if($code == 0){
								$attach->appendChild($arRes['xml']->createElement(
										'item'
										,array('name'=>$files['name'][$i],'path'=>$files['tmp_name'][$i],'size'=>$files['size'][$i])
								));
							}
						}
						break;
					case false:
						if(($files['error'] == 0)){
							$attach->appendChild($arRes['xml']->createElement(
									'item'
									,array('name'=>$files['name'],'path'=>$files['tmp_name'],'size'=>$files['size'])
							));
						}
						break;
				}
				break;
			case 'password':
				$f['value'] = md5($val);
				break;
			case 'checkbox':
				$f['value'] = isset($_REQUEST[$field->getAttribute('name')]) ? "1" : "0";
				break;
			case 'textarea':
				$f['value'] = nl2br(strip_tags($val));
				break;
			default:
				$f['value'] = strip_tags($val);
		}					
		if($field->hasAttribute('mail')){
			$arRes['xml']->de()->appendChild($arRes['xml']->createElement('field',array('name'=>$field->getAttribute('name'),'label'=>$f['label']),($field->getAttribute('type') == 'password')?$val:$f['value']));
		}
		if($field->hasAttribute('uri')){
			$arRes['mysql'][$field->getAttribute('name')] = $f['value'];
		}
	}
	
	if(!($arRes['xml']->query('//@label')->item(0) instanceof DOMAttr)) unset($arRes['xml']);
	if(!$form->getAttribute('dbSave')) unset($arRes['mysql']);
	return $arRes;
}
function sendEmail($xml,$form){
	global $_site;
	// отправляем почту админу и дублируем пользователю, если есть почтовые поля
	
	if(($xml->query('./field',$xml->de())->item(0) || $xml->query('./attach',$xml->de())->item(0))
		&& ($domain = $_site->de()->getAttribute('domain')) //site domain
		&& $xml->de()->setAttribute('domain',$domain)
		&& $xml->de()->setAttribute('name',$_site->de()->getAttribute('name'))
		//формируем почтовое сообщение для администратора
		&& ($tpl = new template(
			is_file(PATH_TPL.$form->getAttribute('emailTpl').'.xsl')?
				PATH_TPL.$form->getAttribute('emailTpl').'.xsl':
				PATH_TPL.'email_feedback.xsl' //default template, installed with module install.
		))
		&& ($content = $tpl->transform($xml))
		&& ($email = $form->getAttribute('email') ? //admin email
				$form->getAttribute('email') : 
				($_site->de()->getAttribute('email') 
				.($_site->de()->getAttribute('email2') ? ','.$_site->de()->getAttribute('email2') : null) //доп. мыло
				.($_site->de()->getAttribute('email3') ? ','.$_site->de()->getAttribute('email3') : null)) ) //доп. мыло
		&& ($subject = $form->hasAttribute('subject')?$form->getAttribute('subject'):'Новое сообщение от пользователя с сайта - '.$domain)
		&& ($mail = new mymail($_site->de()->getAttribute('fromEmail'),$email,$subject,$content)) //объект для отправки письма
		//&& ($mail = new mymail('pgood@forumedia.com',$email,$subject,$content)) //для отладки
		&& ($mail1 = new mymail($_site->de()->getAttribute('fromEmail'),'katerina@forumedia.ru',$subject,$content)) //объект для отправки письма
		&& $this->mailAttach($mail,$xml)
		&& $this->mailAttach($mail1,$xml)
		&& @$mail->send() //send email to admin
		&& @$mail1->send() //send email to admin
		//формируем почтовое сообщение для пользователя
		&& ($form->getAttribute('sendUser') ? $this->sendEmailUser($xml,$form):true)
	) return true;
	else return false;
}
function sendEmailUser($xml,$form){
	global $_site;
	if(($tpl = new template(
			is_file($this->getSection()->getTemplatePath().$form->getAttribute('emailTplUser').'.xsl')?
				$this->getSection()->getTemplatePath().$form->getAttribute('emailTplUser').'.xsl':
				$this->getSection()->getTemplatePath().'email_feedback_user.xsl' //default template, installed with module install.
		))
		&& ($domain = $_site->de()->getAttribute('domain')) //site domain
		&& ($content = $tpl->transform($xml))
		&& ($email = param('email')/* $xml->evaluate('string(//field[@name="email"]/text())')*/) //мыло пользователя
		&& ($subject = $form->getAttribute('theme')?$form->getAttribute('themeUser'):'Вами было отправлено сообщение с сайта - '.$domain)
		&& ($mail = new mymail($_site->de()->getAttribute('fromEmail'),$email,$subject,$content)) //объект для отправки письма
		&& @$mail->send() //отправляем письмо
	)return true;
	else return false;
}
function mailAttach(&$mail,$xml){
	if($attach = $xml->query('./attach',$xml->de())->item(0)){
		$items = $xml->query('./item',$attach);
		foreach($items as $item){
			if(!$mail->attach($item->getAttribute('path'),  pathinfo($item->getAttribute('name'),PATHINFO_BASENAME)))
				return false;
		}
	}
	return true;
}
function fillForm($form){
	$res = $this->query('.//field',$form);
	foreach($res as $field){
		switch($field->getAttribute('type')){
			case 'radio':
				$opts = $this->query('option',$field);
				foreach($opts as $opt){
					$val = $opt->hasAttribute('value') ? $opt->getAttribute('value') : $this->evaluate('string(text())',$opt);
					if($val==stripslashes(param($field->getAttribute('name')))){
						$opt->setAttribute('checked','checked');
						break;
					};
				}
				break;
			case 'checkboxgroup':
				$opts = $this->query('option',$field);
				foreach($opts as $j => $opt){
					$val = $opt->hasAttribute('value') ? $opt->getAttribute('value') : $this->evaluate('string(text())',$opt);
					if($val==param($field->getAttribute('name').$j))
						$opt->setAttribute('checked','checked');
				}
				break;
			case 'select':
				$field->setAttribute('value',param($field->getAttribute('name')));
				break;
			default:
				$field->appendChild($field->ownerDocument->createTextNode(param($field->getAttribute('name'))));
		}
	}
	$this->formMessage(implode('<br/>',$this->mess),$form);
}
/*
 * @todo optimize for different table, for cross-platform.
 */
function insertDB($data,$e){ //@todo udaptate with tables fields
	if($form = new form($e)){
		$form->replaceURI(array('CONNECT'=>$e->getAttribute('dbConnect'),'TABLE'=>$e->getAttribute('dbTable')));
		$params = array();
		$paramsXml = $this->getXML()->query('param[@uri]',$e);
		foreach($paramsXml as $param){
			if(!$data[$param->getAttribute('name')])
				$params[$param->getAttribute('name')] = ($param->getAttribute('name') == 'sort')?$this->getNextSortIndex($e):$param->getAttribute('value');
		}
		$data = array_merge($data,$params);
		/*$data = array_merge($data,array(
			 'section'	=> $this->getName()
			,'module'	=> $this->getId()
			,'active'	=> 1
			,'sort'		=> $this->getNextSortIndex()
		));*/
		$form->save($data);
	}else {
		$this->err('Form not found');
		return false;
	}
	return true;
}
function getNextSortIndex($form){
	$mysql = new mysql();
	$index = 1;
	$rs = $mysql->query('select max(`sort`)+1 as `new_sort_index`
		from `'.$mysql->getTableName($form->getAttribute('dbTable')).'`
		where `section`="'.$this->getName().'" AND `module`="'.$this->getId().'"');
	if($rs && ($row = $mysql->fetch($rs)) && $row['new_sort_index']) $index = $row['new_sort_index'];
	return $index;
}
function getXML(){
	return new xml($this->getRootElement()->ownerDocument);
}
function run(){
	global $_out;
	
	$ns = $this->query('form');
	foreach($ns as $form){
		//if($this->getSection()) vdump($this->hasCaptcha($form));
		
		if(!$form->getAttribute('action')) $form->setAttribute('action',$_SERVER['REQUEST_URI']);
				
		if($this->isSent($form)){ //форму отправили
			$xml = new xml($form);
			if(!$this->check($form) && ($res = $this->getSentData($form))){
				$resultSQL = $resultMail = true;
				if(!empty($res['mysql']))
					$resultSQL = $this->insertDB($res['mysql'],$form);
				if(!empty($res['xml']))
					$resultMail = $this->sendEmail($res['xml'],$form);
				$form->appendChild($xml->createElement('message'
					,array('type' => $resultSQL && $resultMail ? 'success' : 'danger')
					,xml::getElementText($this->query($resultSQL && $resultMail ? 'form/good' : 'form/fail')->item(0))
				));
			}else{ // Ошибка - заполняем форму
				$this->fillForm($form);
			}
		}
		if($form->hasAttribute('appendTo'))
			$_out->elementIncludeTo($form,$form->getAttribute('appendTo'));
		elseif(!$this->getSection())
			$_out->elementIncludeTo($form,$_out->de());
		else
			$_out->addSectionContent($form);
		
        if($this->hasCaptcha($form)){
			$captcha = new captcha();
			$captcha->setLanguage($_out->getLang());
			$captcha->setParamName('captcha');
            $captcha->create('userfiles/cptch.jpg');
		}
	}
}
function formMessage($str,$form){
	$xml = new xml($form);
	return $form->appendChild($xml->createElement('message',null,$str));
}
}
