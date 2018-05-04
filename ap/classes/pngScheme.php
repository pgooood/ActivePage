<?php
class pngScheme extends jpgScheme{
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
		,isset($param['alpha'])	? !!$param['alpha']		: true
		,isset($param['max'])	? $param['max']			: 1024
		,'png'
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