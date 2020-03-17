<?
class mymail{
	var $to = '';
	var $from = '';
	var $reply_to = '';
	var $cc = '';
	var $bcc = '';
	var $subject = '';
	var $body = '';
	var $body_parts = array();
	var $validate_email = true;
	var $rigorous_email_check = false;
	var $allow_empty_subject = true;
	var $allow_empty_body = true;
	var $headers = array();
	var $attach = array();
	var $charset = 'utf-8';
	var $ERROR_MSG;
	var $ERR_EMPTY_MAIL_TO = 'Не указан адресат';
	var $ERR_EMPTY_SUBJECT = 'Не указана тема сообщения';
	var $ERR_EMPTY_BODY = 'Не введен текст сообщения';
	var $ERR_TO_FIELD_INVALID = 'Адресат указан неверно';
	var $ERR_CC_FIELD_INVALID = 'Адресат указан неверно';
	var $ERR_BCC_FIELD_INVALID = 'Адресат указан неверно';
	var $ERR_SEND_MAIL_FAILURE = 'Ошибка отправки сообщения';
	
	function __construct($from,$to,$subject,$content){
		$this->mymail($from,$to,$subject,$content);
	}
	function mymail($from,$to,$subject,$content){
		$this->from = $from;
		$this->to = $to;
		$this->subject = $subject;
		$this->setContent($content);
	}
	function setContent($val){
		$this->body = $val;
	}
	function getContent(){
		return $this->body;
	}
	function checkFields(){
		if(empty($this->to)){
			$this->ERROR_MSG = $this->ERR_EMPTY_MAIL_TO;
			return false;
		}
		if(!$this->allow_empty_subject && empty($this->subject)){
			$this->ERROR_MSG = $this->ERR_EMPTY_SUBJECT;
			return false;
		}
		if(!$this->allow_empty_body && empty($this->body)){
			$this->ERROR_MSG = $this->ERR_EMPTY_BODY;
			return false;
		}
		$this->to = ereg_replace(";",",",$this->to);
		$this->cc = ereg_replace(";",",",$this->cc);
		$this->bcc= ereg_replace(";",",",$this->bcc);
		if($this->validate_email){
			$to_emails = explode(',',$this->to);
			$cc_emails = $bcc_emails = null;
			if(!empty($this->cc)) $cc_emails = explode(',',$this->cc);
			if(!empty($this->bcc)) $bcc_emails = explode(',',$this->bcc);
			if($this->rigorous_email_check){
				if(!$this->rigorousEmailCheck($to_emails)){
					$this->ERROR_MSG = $this->ERR_TO_FIELD_INVALID;
					return false;
				}elseif(is_array($cc_emails) && !$this->rigorousEmailCheck($cc_emails)){
					$this->ERROR_MSG = $this->ERR_CC_FIELD_INVALID;
					return false;
				}elseif(is_array($bcc_emails) && !$this->rigorousEmailCheck($bcc_emails)){
					$this->ERROR_MSG = $this->ERR_BCC_FIELD_INVALID;
					return false;
				}
			}else{
				if(!$this->emailCheck($to_emails)){
					$this->ERROR_MSG = $this->ERR_TO_FIELD_INVALID;
					return false;
				}elseif(is_array($cc_emails) && !$this->emailCheck($cc_emails)){
					$this->ERROR_MSG = $this->ERR_CC_FIELD_INVALID;
					return false;
				}elseif(is_array($bcc_emails) && !$this->emailCheck($bcc_emails)){
					$this->ERROR_MSG = $this->ERR_BCC_FIELD_INVALID;
					return false;
				}
			}
		}
		return true;
	}
	static function isEmail($email){
		$match = null;
		if(preg_match('/<(.+)>/',$email,$match))
			$email = $match[1];
		return filter_var($email,FILTER_VALIDATE_EMAIL);
	}
	function emailCheck($emails){
		if(!is_array($emails))
			$emails = array($emails);
		foreach($emails as $email)
			if(!self::isEmail($email))
				return false;
		return true;
	}
	function rigorousEmailCheck($emails){
		if(!$this->rigorous_email_check) return false;
		foreach($emails as $email){
			list($user,$domain) = split("@",$email,2);
			if(checkdnsrr($domain,'ANY')) return true;
			else return false;
		}
	}
	function buildHeaders(){
		$this->boundary = md5(uniqid(time()));
		if(!empty($this->from)) $this->headers['From'] = "From: $this->from";
		if(!empty($this->reply_to)) $this->headers['Reply-To'] = "Reply-To: $this->reply_to";
		if(!empty($this->cc)) $this->headers['Cc'] = "Cc: $this->cc";
		if(!empty($this->bcc)) $this->headers['Bcc'] = "Bcc: $this->bcc";
		$this->headers['X-Mailer'] = "X-Mailer: PHP/".phpversion();
		$this->headers['MIME-Version'] = "MIME-Version: 1.0";
		$this->headers['Content-Type'] = "Content-Type: text/html; charset=\"$this->charset\"";
	}
	function prepareBody(){
	}
	function send(){
		if(!$this->checkFields()) return false;
		if(count($this->attach)) return $this->sendMultipart();
		$this->buildHeaders();
		$this->prepareBody();
		if(mail($this->to,
			'=?'.$this->charset.'?B?'.base64_encode(trim($this->subject)).'?=',
			$this->body,
			implode("\n",$this->headers))) return true;
		else $this->ERROR_MSG = $this->ERR_SEND_MAIL_FAILURE;
		return false;
	}
	private function sendMultipart(){
		$un = md5(uniqid(rand(),true));
		$this->buildHeaders();
		$this->prepareBody();
		$this->headers['Content-Type'] = 'Content-Type: multipart/mixed; boundary="'.$un.'"';
		
		//содержание
		$content = "--".$un."\n";
		$content.= "Content-Type: text/html; charset=\"utf-8\"\n";
		$content.= "Content-Transfer-Encoding: 8bit\n\n";
		$content.= trim($this->body)."\n\n\n";
		
		foreach($this->attach as $name => $path){
			if($file = fopen($path,'r')){
				$content.= "--" . $un . "\n";
				$content.= "Content-Type: application/binary; name=\"".$name."\"\n";
				$content.= "Content-Transfer-Encoding: base64\n";
				$content.= "Content-ID: <".md5($path).">\n\n";
				$content.= chunk_split(base64_encode(fread($file,filesize($path))))."\n\n"; 
				fclose($file);
			}
		}
		$content.= "--".$un."--\n";
		if(mail($this->to,
			'=?'.$this->charset.'?B?'.base64_encode(trim($this->subject)).'?=',
			$content,
			implode("\n",$this->headers))) return true;
		else $this->ERROR_MSG = $this->ERR_SEND_MAIL_FAILURE;
		return false;
	}
	function attach($path,$name){
		if(file_exists($path) && filesize($path)<=3145728){
			$this->attach[$name] = $path;
			return true;
		}
		return false;
	}
	function errorMsg(){
		if(!empty($this->ERROR_MSG)) return $this->ERROR_MSG;
		return '';
	}
}
