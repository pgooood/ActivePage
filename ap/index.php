<?
/*
 * Версия 6.2 с разграничением прав пользователей
 */
mb_internal_encoding('utf-8');
setlocale(LC_ALL,'ru_RU.UTF8');
function autoload($class){
	if((substr($class,0,2)=='ap' && is_file($path = 'modules/'.$class.'/'.$class.'.php'))
		|| is_file($path = 'classes/'.$class.'.php')
		|| is_file($path = '../classes/'.$class.'.php')
	) require_once $path;
}
spl_autoload_register('autoload');
require 'lib/default.php';
require 'classes/xml.php';
require 'classes/site.php';
require 'classes/out.php';
require 'classes/structure.php';
require 'classes/section.php';
require 'classes/modules.php';
require 'classes/module.php';
require 'classes/template.php';
require 'classes/events.php';
require 'modules/ap/ap.php';

define('EXCEPTION_404',1);
define('EXCEPTION_MYSQL',2);
define('EXCEPTION_TPL',3);
define('EXCEPTION_XML',4);

define('PATH_MODULE'			,'modules/');
define('PATH_XML_LOCALE'		,'xml/');
define('PATH_DATA'				,PATH_XML_LOCALE.'data/');
define('PATH_TPL'				,'xml/templates/');
define('PATH_USERS'				,'xml/users.xml');

//пути к файлам относительно корня сайта (используются в XML урлах)
define('ABS_PATH_SITE'			,'xml/site.xml');
define('ABS_PATH_STRUCT_CLIENT'	,'xml/structure.xml');
define('ABS_PATH_DATA_CLIENT'	,'xml/data/');
define('ABS_PATH_TPL_CLIENT'	,'xml/templates/');
define('ABS_PATH_DATA_AP'		,'ap/xml/data/');
define('ABS_PATH_USERS'			,'ap/xml/users.xml');

//пути к файлам клиентской части относительно текущей папки
define('PATH_ROOT'				,'../');
define('PATH_STRUCT_CLIENT'		,PATH_ROOT.ABS_PATH_STRUCT_CLIENT);
define('PATH_TPL_CLIENT'		,PATH_ROOT.ABS_PATH_TPL_CLIENT);

try{
	$_site = new site(PATH_ROOT.ABS_PATH_SITE);
	$_struct = new structure('xml/structure.xml',PATH_DATA,PATH_TPL);
	$_out = new out($ln);
	$_events = new events('xml/events.xml');
	$_events->addEvent('SectionReady');
	$_events->addEvent('PageReady');
	
	$_site->setModules(new modules($_site,'apModules'));
	$modules = $_site->getModules();
	if(!$modules->hasModule('ap'))
		$modules->move($modules->add('ap'),1);
	$modules->run();
	
	$_sec = $_struct->getCurrentSection();
	$_events->happen('SectionReady');
	
	$_sec->getModules()->run();
	$_out->xmlInclude($_struct);
	$_out->xmlInclude($_site);
	$_out->xmlInclude(PATH_USERS);
	$_events->happen('PageReady');
	$_tpl = $_sec->getTemplate();

	$tmp = explode('/',trim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/'));
	array_pop($tmp);
	$_out->de()->setAttribute('base_url','/'.implode('/',$tmp).'/');
	
	//$_out->save('temp.xml');
	echo $_tpl->transform($_out);
}catch(Exception $e){
	$_out = new out();
	$_out->addSectionContent('Exception: '.$e->getMessage().'<hr style="margin:10px 0;">'.nl2br($e->getTraceAsString()));
	$_tpl = new template('xml/templates/error.xsl');
	echo $_tpl->transform($_out);
}