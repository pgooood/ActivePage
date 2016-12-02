<?
$filename = null; //путь к картинке
$max_big_side_len = isset($_GET['max']) && intval($_GET['max']) > 0 ? intval($_GET['max']) : 1024; //максимальный размер длинной стороны картинки в пикселях

if(isset($_GET['src'])) $filename = urldecode($_GET['src']);
$isUrl = parse_url($filename,PHP_URL_SCHEME);
if(!$filename || (!$isUrl && !is_file($filename))) die('file ('.$filename.') not found');

//выравнивание при обрезании картинки
$hAlign = isset($_GET['ha']) ? $_GET['ha'] : 'center';
$vAlign = isset($_GET['va']) ? $_GET['va'] : 'middle';

/*
$pi = pathinfo($filename);
$cacheFileName = $pi['filename'].implode('x',)
print_r(pathinfo($filename));
die;
*/

//проверяем расширение файла и выясняем поддерживает ли PHP формат изображения
$image_type = 0x0;
switch(strtolower(pathinfo($filename,PATHINFO_EXTENSION))){
	case 'gif': $image_type |= IMG_GIF; break;
	case 'png': $image_type |= IMG_PNG; break;
	case 'jpg':
	case 'jpeg': $image_type |= IMG_JPG; break;
	default: die('wrong file type');
}
if(!(imagetypes() & $image_type)) die('unsupported image type');

//получаем новые размеры картинки
$w = isset($_GET['w']) ? intVal($_GET['w']) : null;
$h = isset($_GET['h']) ? intVal($_GET['h']) : null;
list($width,$height) = getimagesize($filename);

$x_source = $y_source = 0;
if($w && $h){ //масштабируем по заданным пропорциям
	if($height*$w/$width < $h){
		$temp_width=($height*$w)/$h;
		switch($hAlign){
			case 'left': $x_source = 0; break;
			case 'right': $x_source = $width-$temp_width; break;
			default: $x_source = ($width-$temp_width)/2;
		}
		$width = $temp_width;
	}elseif($width*$h/$height < $w){
		$temp_height = ($width*$h)/$w;
		switch($vAlign){
			case 'top': $y_source = 0; break;
			case 'bottom': $y_source = $height-$temp_height; break;
			default: $y_source = ($height-$temp_height)/2;
		}
		$height = $temp_height;
	}
}elseif($w){ //вычисляем высоту по заданной ширине
	$h = $w*$height/$width;
}elseif($h){ //вычисляем ширину по заданной высоте
	$w = $h*$width/$height;
}elseif($width>=$height && $width>$max_big_side_len){ //уменьшаем слишком широкие
	$w = $max_big_side_len;
	$h = $w*$height/$width;
}elseif($width<$height && $height>$max_big_side_len){ //уменьшаем слишком высокие
	$h = $max_big_side_len;
	$w = $h*$width/$height;
}else{ //оставляем как есть
	$w = $width;
	$h = $height;
}

//Изменяем и выводим картинку
$image_p = imagecreatetruecolor($w,$h);
$image = null;
switch($image_type){
	case IMG_GIF: $image = imagecreatefromgif($filename); break;
	case IMG_PNG: $image = imagecreatefrompng($filename); break;
	case IMG_JPG: $image = imagecreatefromjpeg($filename); break;
}
imagecopyresampled($image_p, $image, 0, 0, $x_source, $y_source, $w, $h, $width, $height);
header('Content-type: image/jpeg');
imagejpeg($image_p,null,90);
imageDestroy($image_p);
imageDestroy($image);
die;
?>