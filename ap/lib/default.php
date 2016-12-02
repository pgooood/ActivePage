<?
function param($name,$value = null){
	$fieldName='';
	if($value!==null) setParam($name,$value);
	if(preg_match('/^([^\[]+)\[([^\]]*)\]$/',$fieldName,$m))
		$name = $m[1];
	return isset($_REQUEST[$name]) ? $_REQUEST[$name] : null;
}
function lang($mode,$default){
	$name = 'ap_lang_'.$mode;
	$ln = param('ln');
	if($ln = param('ln'));
	elseif(isset($_COOKIE[$name])) $ln = $_COOKIE[$name];
	else $ln = $default;
	if(!isset($_COOKIE[$name]) || $_COOKIE[$name]!=$ln) setcookie($name,$ln,time()+7776000);
	return $ln;
}
function setParam($name,$value,$method = 'GET',$add_slashes = false){
	$_REQUEST[$name] = $add_slashes ? addslashes($value) : $value;
	switch(strtoupper($method)){
		case 'GET': $_GET[$name] = $_REQUEST[$name]; break;
		case 'POST': $_POST[$name] = $_REQUEST[$name]; break;
	}
}
function vdump($v,$die = true){
	if($die){
		throw new Exception('<pre>'.print_r($v,true).'</pre>');
	}else{
		?><pre><? print_r($v) ?></pre><?
	}
}
function translit($str){
	$maxStrLen = 64;
	$table = array(
		'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i',
		'й'=>'j','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t',
		'у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'csh','ь'=>'','ы'=>'y','ъ'=>'',
		'э'=>'e','ю'=>'yu','я'=>'ya',' '=>'_'
	);
	$str = mb_strtolower($str);
	$str = str_replace(array_keys($table),array_values($table),$str);
	$str = trim(preg_replace('/[^a-zA-Z0-9_]+/','',$str),'_');
	if(strlen($str)>$maxStrLen) $str = substr($str,0,$maxStrLen);
	return $str;
}