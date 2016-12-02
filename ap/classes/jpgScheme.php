<?php
class jpgScheme{
private $values = array();
private $cache = array();
const VALUE_DELETE = 'delete';
function add($uri,$value){
	$this->values[$uri] = $value;
}
function save(){
	$str = '';
	foreach($this->values as $uri => $value){
		if(($v = $this->parseURI($uri))
			&& $this->checkPath($v['path'])
		){
			$str.= $uri.' ::: '.$value.'<br>';
			if($value==jpgScheme::VALUE_DELETE){
				if(file_exists($v['path'])) unlink($v['path']);
			}elseif($value) $this->saveImage($value,$v['path'],$v['params']);
			
		}
	}
}
function get($uri){
	if(($v = $this->parseURI($uri))
		&& $v['url']
		&& file_exists($v['path'])
	){
		return $v['path'];
	}
}
function delete($uri){
	if(($v = $this->parseURI($uri))
		&& $v['path']
		&& file_exists($v['path'])
	) return unlink($v['path']);
}
static function parseURI($uri){
	if(!preg_match('/%([A-Z0-9_]+)%/',$uri,$matches)
		&& ($url = parse_url($uri))
		&& isset($url['path'])
	){
		$params = array();
		if(isset($url['query']) && $url['query']){
			$tmp1 = explode('&',$url['query']);
			foreach($tmp1 as $pair){
				$tmp3 = explode('=',$pair);
				$params[$tmp3[0]] = $tmp3[1];
			}
		}
		return array(
			'url' => $url['path'],
			'path' => PATH_ROOT.trim($url['path'],'/'),
			'params' => $params
		);
	}
}
/**
* При необходимости создает директории и/или изменяет права доступа
* Возвращет правду если по заданому пути можно сохранить файл
*/
function checkPath($src){
	$path = explode('/',pathinfo($src,PATHINFO_DIRNAME));
	$dirToCreate = array();
	while($dir = implode('/',$path)){
		if(is_dir($dir)){
			break;
		}elseif(($dirName = array_pop($path))
			&& $dirName!='..'
			&& $dirName!='.'
		) array_unshift($dirToCreate,$dirName);
		else return false;
	}
	foreach($dirToCreate as $dirName){
		if(!mkdir($dir = $dir.'/'.$dirName,0755)) return false;
	}
	$path = file_exists($src) ? $src : pathinfo($src,PATHINFO_DIRNAME);
	if(!is_writable($path)) chmod($path,0777);
	return is_writable($path);
}
/**
* Изменяет и сохраняет изображение
*/
function saveImage($src,$dst,$param = null){
	$img = new images(
		$src
		,isset($param['w'])		? intval($param['w'])	: null
		,isset($param['h'])		? intval($param['h'])	: null
		,isset($param['fixed'])	? true					: false
		,isset($param['ha'])	? $param['ha']			: 'center'
		,isset($param['va'])	? $param['va']			: 'middle'
		,isset($param['rgb'])	? $param['rgb']			: '255,255,255'
		,isset($param['alpha'])	? !!$param['alpha']		: false
		,isset($param['max'])	? $param['max']			: 1024
		,'jpg'
	);
	if(isset($param['waterMark'])){
		$water = new images(
			$param['waterMark']
			,isset($param['waterW'])	? intval($param['waterW'])	: null
			,isset($param['waterH'])	? intval($param['waterH'])	: null
			,isset($param['waterFixed'])? true						: false
			,isset($param['waterHa'])	? $param['waterHa']			: 'center'
			,isset($param['waterVa'])	? $param['waterVa']			: 'middle'
			,isset($param['waterRGB'])	? $param['waterRGB']		: null
			,isset($param['waterAlpha'])? !!$param['waterAlpha']	: true
			,isset($param['waterMax'])	? $param['waterMax']		: 1024
			,'png'
		);
		$img->addWatermark(
			$water
			,isset($param['waterOffsetX']) ? $param['waterOffsetX'] : 0
			,isset($param['waterOffsetY']) ? $param['waterOffsetY'] : 0
			,isset($param['waterOpacity']) ? $param['waterOpacity'] : 100
			,isset($param['waterAlignH']) ? $param['waterAlignH']	: null
			,isset($param['waterAlignV']) ? $param['waterAlignV']	: null
		);
	}
	$res = $img->save($dst);
	if(isset($param['waterMark'])) $water->__destruct();
	$img->__destruct();
	return $res;
}
}