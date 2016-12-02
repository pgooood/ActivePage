<?php
/**
 * Description of images
 *
 * @author dev-kirill
 */
class images {
	private $image;
	private $h;
	private $w;
	private $offsetX;
	private $offsetY;
	private $imageSource;
	private $hSource;
	private $wSource;
	private $offsetXSource;
	private $offsetYSource;
	private $hAlign;
	private $vAlign;
	private $type;
	private $max;
	
	function __construct($src
		,$w = null,$h = null
		,$fixed = false
		,$hAlign = null,$vAlign = null
		,$rgb = null
		,$alpha = false
		,$max = 1280
		,$type = 'jpg'
	){
		$this->setType($type);
		
		if($filename = $this->checkFile($src)){ // file exist, continue.
			
			$type = self::getTypeByExt(pathinfo($filename,PATHINFO_EXTENSION));
			if(!(imagetypes() & $type))
				throw new Exception('Unsupported image type');
			
			//init params
			$this->max		= isset($max) && intval($max) > 0 ? intval($max) : 1024;
			$this->hAlign	= isset($hAlign) ? $hAlign : 'center';
			$this->vAlign	= isset($vAlign) ? $vAlign : 'middle';
			$this->w		= isset($w) ? intVal($w) : null;
			$this->h		= isset($h) ? intVal($h) : null;
			
			list($this->wSource,$this->hSource) = getimagesize($filename); //real sizes
			
			$this->offsetX = $this->offsetY = $this->offsetXSource = $offsetY = 0; // offset X, Y
			if($this->w && $this->h){ //resize param for picture
				if($fixed){
					$r = ($this->wSource > $this->hSource) ? $this->w/$this->wSource : $this->h/$this->hSource;
					$temp_width = ceil($this->wSource*$r);
					$temp_height = ceil($this->hSource*$r);
					if($this->wSource < $this->hSource){
						switch($this->hAlign){
							case 'left':	$this->offsetX = 0; break;
							case 'right':	$this->offsetX = $this->w-$temp_width; break;
							default:		$this->offsetX = ceil(($this->w-$temp_width)/2); break;
						}
					}else{
						switch($this->vAlign){
							case 'top':		$this->offsetY = 0; break;
							case 'bottom':	$this->offsetY = $this->h-$temp_height; break;
							default:		$this->offsetY = ceil(($this->h-$temp_height)/2); break;
						}
					}
					$this->w = $temp_width;
					$this->h = $temp_height;
				}else{
					if($this->w*$this->hSource/$this->wSource < $this->h){
						$temp_width=ceil(($this->hSource*$this->w)/$this->h);
						switch($this->hAlign){
							case 'left':	$this->offsetXSource = 0; break;
							case 'right':	$this->offsetXSource = $this->wSource-$temp_width; break;
							case 'center':	$this->offsetXSource = ceil(($this->wSource-$temp_width)/2); break;
						}
						$this->wSource = $temp_width;	
					}elseif($this->h*$this->wSource/$this->hSource < $this->w){
						$temp_height = ceil(($this->wSource*$this->h)/$this->w);
						switch($this->vAlign){
							case 'top':		$this->offsetYSource = 0; break;
							case 'bottom':	$this->offsetYSource = $this->hSource-$temp_height; break;
							case 'middle':	$this->offsetYSource = ceil(($this->hSource-$temp_height)/2); break;
						}
						$this->hSource = $temp_height;
					}
				}
			}elseif($this->w){ //calculate for width
				$this->h = ceil($this->w*$this->hSource/$this->wSource);
			}elseif($this->h){ //calculate for height
				$this->w = ceil($this->h*$this->wSource/$this->hSource);
			}elseif($this->wSource>=$this->hSource && $this->wSource>$this->max){ //too big width
				$this->w = $this->max;
				$this->h = ceil($this->w*$this->hSource/$this->wSource);
			}elseif($this->wSource<$this->hSource && $this->hSource>$this->max){ //too big height
				$this->h = $this->max;
				$this->w = ceil($this->h*$this->wSource/$this->hSource);
			}else{ //no resizing
				$this->w = $this->wSource;
				$this->h = $this->hSource;
			}
			
			$this->image = imagecreatetruecolor($this->w,$this->h); // result image
			$this->imageSource = null; //source temp image
			
			switch($type){
				case IMG_GIF: $this->imageSource = imagecreatefromgif($filename); break;
				case IMG_PNG: $this->imageSource = imagecreatefrompng($filename); break;
				case IMG_JPG: $this->imageSource = imagecreatefromjpeg($filename); break;
			}
			if(false){//preserve alpha
				imagecolortransparent($this->image, imagecolorallocate($this->image, 255, 255, 255));
				imagealphablending($this->image, false);
				imagesavealpha($this->image, true);
				imagecolortransparent($this->imageSource, imagecolorallocate($this->imageSource, 255, 255, 255));
				imagealphablending($this->imageSource, false);
				imagesavealpha($this->imageSource, true);
			}
			
			//заливаем фон заданным цветом, если это не PNG с альфаканалом
			if($rgb
				&& !($this->type === IMG_PNG && $alpha)
				&& count($arRGB = explode(',',$rgb)) == 3
				&& ($red = intval($arRGB[0])) >= 0 && $red <= 255
				&& ($green = intval($arRGB[1])) >= 0 && $green <= 255
				&& ($blue = intval($arRGB[2])) >= 0 && $blue <= 255
			){
				//vdump($arRGB);
				$color = imagecolorallocate($this->image,$red,$green,$blue);
				imagefill($this->image,0,0,$color);
			}

			imagecopyresampled(
				$this->image,
				$this->imageSource,
				$this->offsetX,
				$this->offsetY,
				$this->offsetXSource,
				$this->offsetYSource,
				$this->w,
				$this->h,
				$this->wSource,
				$this->hSource
			);

		}else{
			die('file not found');
		}
	}
	public function __destruct() {
		#imagedestroy($this->image);
		#imagedestroy($this->imageSource);
		unset($this->max);
		unset($this->h);
		unset($this->w);
		unset($this->hSource);
		unset($this->wSource);
		unset($this->offsetXSource);
		unset($this->offsetYSource);
		unset($this->offsetX);
		unset($this->offsetY);
		unset($this->hAlign);
		unset($this->vAlign);
		unset($this->type);
	}
	function getImage($source = false){
		return (!$source)?$this->image:$this->imageSource;
	}
	function getWidth($source = false){
		return (!$source)?$this->w:$this->wSource;
	}
	function getHeight($source = false){
		return (!$source)?$this->h:$this->hSource;
	}
	function addWatermark($water,$offsetX=5,$offsetY=5,$transparancy=100,$hAlign=null,$vAlign=null){
		$offsetX = ($hAlign == 'right') ?$this->getWidth()	- $offsetX - $water->getWidth() + $this->offsetX:$offsetX + $this->offsetX;
		$offsetY = ($vAlign == 'bottom')?$this->getHeight()	- $offsetY - $water->getHeight() + $this->offsetY:$offsetY + $this->offsetY;
		$transparancy = ($transparancy < 0)? 0 : (($transparancy > 100)? 100 :$transparancy);
		$tmp = imagecreatetruecolor($this->w, $this->h); 
		
		imagecopy($tmp, $this->image, 0, 0, $offsetX, $offsetY, $water->getWidth(), $water->getHeight()); // copying relevant section from background to the tmp resource 
		imagecopy($tmp, $water->getImage(), 0, 0, 0, 0, $water->getWidth(),$water->getHeight()); // copying relevant section from watermark to the tmp resource 		
		imagecopymerge($this->image, $tmp, $offsetX, $offsetY, 0, 0, $water->getWidth(), $water->getHeight(), $transparancy); // insert tmp resource to destination image 
		
		/* //this methods not support transparency
		imagecopyresampled($this->image,$water->getImage(),$offsetX,$offsetY,0,0,$water->getWidth(),$water->getHeight(),$water->getWidth(),$water->getHeight());
		imagecopymerge($this->image,$water->getImage(),$offsetX,$offsetY,0,0,$water->getWidth(),$water->getHeight(),100);
		 */		
	}
	/*
	 * @todo fix angle calc.
	 */
	function addText($text,$fontPath,$fontSize,$offsetX=0,$offsetY=0,$hAlign=null,$vAlign=null,$angle=0,$hexColor=FFFFFF,$transparancy=0){
		if($fontPath = $this->checkFile($fontPath)){
			$angle = 0;
/*
 * 
0	нижний левый угол, X-позиция
1	нижний левый угол, Y-позиция
2	нижний правый угол, X-позиция
3	нижний правый угол, Y-позиция
4	верхний правый угол, X-позиция
5	верхний правый угол, Y-позиция
6	верхний левый угол, X-позиция
7	верхний левый угол, Y-позиция

0:   Array ( [0] => -1 [1] => 2 [2] => 134 [3] => 2 [4] => 134 [5] => -15 [6] => -1 [7] => -15 )
45:  Array ( [0] => 1 [1] => 3 [2] => 108 [3] => -104 [4] => 93 [5] => -119 [6] => -14 [7] => -12 )
90:  Array ( [0] => 2 [1] => -1 [2] => 2 [3] => -152 [4] => -13 [5] => -152 [6] => -13 [7] => -1 )
180: Array ( [0] => -1 [1] => -4 [2] => -142 [3] => -4 [4] => -142 [5] => 15 [6] => -1 [7] => 15 )
270: Array ( [0] => -5 [1] => -1 [2] => -5 [3] => 151 [4] => 10 [5] => 151 [6] => 10 [7] => -1 )
360: Array ( [0] => -1 [1] => 2 [2] => 134 [3] => 2 [4] => 134 [5] => -15 [6] => -1 [7] => -15 )
* 
*/
			//$angle = ((int)$angle > 359) ? 0 : (((int)$angle < 0)? 0 : (int)$angle);
			$rgb = $this->hex2rgb($hexColor);
			//$r = ((int)$r > 255) ? 255 : (((int)$r < 0)? 0 : (int)$r);//$g = ((int)$g > 255) ? 255 : (((int)$g < 0)? 0 : (int)$g);//$b = ((int)$b > 255) ? 255 : (((int)$b < 0)? 0 : (int)$b);
			$transparancy = ((int)$transparancy < 0)? 0 : (((int)$transparancy > 127)? 127 :(int)$transparancy);
			$offsetXAngle = $offsetYAngle = 0;
			
			$box = imagettfbbox ($fontSize, $angle, $fontPath, $text);
			if(($angle >= 0 && $angle <= 90 ) || ($angle >= 180 && $angle <= 269)){
				$offsetXAngle = ($hAlign == 'right')? abs($box[0] - $box[2]) : abs($box[0] - $box[6]);
				$offsetYAngle = ($vAlign == 'bottom')? abs($box[7] - $box[1]) : abs($box[5] - $box[7]);
			}
			$offsetX = ($hAlign == 'right') ?$this->getWidth() - $offsetXAngle - $offsetX + $this->offsetX:$offsetX + $this->offsetX + $offsetXAngle;
			$offsetY = ($vAlign == 'bottom')?$this->getHeight() - $offsetYAngle - $offsetY + $this->offsetY + $fontSize:$offsetY + $this->offsetY + $fontSize + $offsetYAngle;
			$textColor = imagecolorallocatealpha($this->image,$rgb['red'],$rgb['green'],$rgb['blue'],$transparancy);
			imagettftext(
					$this->image,
					$fontSize,
					$angle,
					$offsetX,
					$offsetY,
					$textColor,
					$fontPath,
					$text
			);
		}else
			die('Font not found');
		
	}
	function save($name){
		switch($this->type){
			case IMG_GIF: $fd = imagegif($this->image,$name); break;
			case IMG_PNG: $fd = imagepng($this->image,$name); break;
			case IMG_JPG: $fd = imagejpeg($this->image,$name,90); break;
		}
		return $fd ? $fd : false;
	}
	function imageStream($source = false){
		switch($this->type){
			case IMG_GIF: header('Content-type: image/gif');  imagegif(!$source?$this->image:$this->imageSource); break;
			case IMG_PNG: header('Content-type: image/png');  imagepng(!$source?$this->image:$this->imageSource); break;
			case IMG_JPG: header('Content-type: image/jpeg'); imagejpeg(!$source?$this->image:$this->imageSource,null,90); break;
		}
		die;
	}
	static function win2uni($s){
		$s = convert_cyr_string($s,'w','i'); // win1251 -> iso8859-5
		for ($result='', $i=0; $i<strlen($s); $i++) {//iso8859-5 -> unicode:
			$charcode = ord($s[$i]);
			$result .= ($charcode>175)?"&#".(1040+($charcode-176)).";":$s[$i];
		}
		return $result;
	}
	static function checkFile($filename){
		if(isset($filename)){
			$filename = urldecode($filename);
			$filename = (substr($filename,0,1)=='/') ? $_SERVER['DOCUMENT_ROOT'].$filename : $filename;
			if($filename && file_exists($filename)) 
				return $filename;
		}
		return false;
	}
	/*
	 * access $color format "0x000000", "FF0000", "#ff0000", 00ff00
	 */
	static function hex2rgb ($color){
		$int = hexdec($color);
		return array("red"	=> 0xFF & ($int >> 0x10),
					"green"	=> 0xFF & ($int >> 0x8),
					"blue"	=> 0xFF & $int);
	}
	
	static protected function getTypeByExt($ext){
		$type = 0x0;
		switch(strtolower($ext)){
			case 'gif': $type |= IMG_GIF; break;
			case 'png': $type |= IMG_PNG; break;
			case 'jpg':
			case 'jpeg': $type |= IMG_JPG; break;
		}
		return $type;
	}
	
	function setType($ext){
		if($type = self::getTypeByExt($ext))
			$this->type = $type;
		return $this->type;
	}
}