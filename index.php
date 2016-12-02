<?php
mb_internal_encoding('utf-8');
function autoload($class){
	if(is_file($path = 'ap/classes/'.$class.'.php') || is_file($path = 'classes/'.$class.'.php'))
		require_once $path;
}
spl_autoload_register('autoload');
require 'ap/lib/default.php';
require 'ap/classes/xml.php';
require 'ap/classes/site.php';
require 'ap/classes/out.php';
require 'ap/classes/structure.php';
require 'ap/classes/section.php';
require 'ap/classes/modules.php';
require 'ap/classes/module.php';
require 'ap/classes/template.php';

define('EXCEPTION_404',1);
define('EXCEPTION_MYSQL',2);
define('EXCEPTION_TPL',3);
define('EXCEPTION_XML',4);

define('PATH_SITE','xml/site.xml');
define('PATH_STRUCT','xml/structure.xml');
define('PATH_DATA','xml/data/');
define('PATH_TPL','xml/templates/');

$temp = pathinfo($_SERVER['PHP_SELF'],PATHINFO_DIRNAME);
if($temp=='\\') $temp = '/';
elseif(substr($temp,-1)!='/') $temp.= '/';
define('BASE_URL',$temp);

class params{
	private $ar;
	function __construct(){$this->ar = params::parse('page','row');}
	function exists($v){return in_array($v,$this->ar);}
	function existsIn($v){return array_search($v,$this->ar);}
	function getIn($pos){if(isset($this->ar[$pos])) return $this->ar[$pos];}
	function pop(){return array_pop($this->ar);}
	function shift(){return array_shift($this->ar);}
	static function get(){
		$uriPath = parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
		$ar = explode('/',trim(
			BASE_URL=='/' || !BASE_URL
				? $uriPath
				: str_replace(BASE_URL,'',$uriPath)
			,'/'));
		foreach($ar as $i => $val) $ar[$i] = urldecode($val);
		return $ar;
	}
	static function parse(){
		$ar = params::get();
		if(!param('__id'))
			param('id',array_shift($ar));
		else
			param('id',param('__id'));
		if(!is_array($ar) || !($num = count($ar))) return array();
		$args = func_get_args();
		foreach($args as $prefix){
			if(!$num) break;
			foreach($ar as $i => $v)
				if(preg_match('/^'.$prefix.'([[0-9a-z_\-]+)$/',$v,$res)){
					param(trim($prefix,'_'),$res[1]);
					unset($ar[$i]);
					$num = count($ar);
					break;
				}
		}
		return array_values($ar);
	}
}
try{
	$_params = new params();
	$_site = new site('xml/site.xml');
	$_struct = new structure('xml/structure.xml');
	$_out = new out();
	
	$_sec = $_struct->getCurrentSection();
	$m = new modules($_site,'modules');
	$m->run();
	$_sec->getModules()->run();
	$_out->xmlInclude($_struct);
	$_out->xmlInclude($_site->getSiteInfo());
	
	$_tpl = $_sec->getTemplate();
	//$_out->save('temp.xml');
	echo $_tpl->transform($_out);
}catch(Exception $e){
	switch($e->getCode()){
		case EXCEPTION_404:
			header("HTTP/1.0 404 Not Found");
			$_site = new site('xml/site.xml');
			$_struct = new structure('xml/structure.xml');
			$_out = new out();
			
			$_out->setMeta('title','404 страница не найдена');
			$_sec = $_struct->addSection('error404','404');
			$_sec->setSelected(true);
			
			$_out->xmlInclude($_struct);
			$_out->xmlInclude($_site->getSiteInfo());
			
			$_tpl = new template($_struct->getTemplatePath().'default.xsl');
			$_tpl->addTemplate($_struct->getTemplatePath().'404.xsl');
			echo $_tpl->transform($_out);
			break;
		default:
			echo 'Exception: '.$e->getMessage().'<hr><pre>'.$e->getTraceAsString().'</pre>';;
	}	
}