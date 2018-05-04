<?php
class announce extends articles{
function run(){
	global $_struct;
	$ns = $this->query('announce');
	foreach($ns as $n){
		if(($idSec = $n->getAttribute('section'))
			&& ($idMod = $n->getAttribute('module'))
			&& ($tagname = $n->getAttribute('name'))
			&& ($sec = $_struct->getSection($idSec))
			&& ($modules = $sec->getModules())
			&& (($m = $modules->getById($idMod)) || ($m = $modules->getByName($idMod)))
		){
			$arParams = array();
			if($n->attributes){
				$arSkip = array('section','module','name','sort','size','parent');
				foreach($n->attributes as $attr)
					if(!in_array($attr->name,$arSkip))
						$arParams[$attr->name] = $attr->value;
			}
			call_user_func(array(&$m,'announce'),$tagname
				,$n->getAttribute('sort')
				,$n->getAttribute('size')
				,$n->getAttribute('parent')
				,$arParams);
		}
	}
}
}