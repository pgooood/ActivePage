<?php
class svgScheme{
	const VALUE_DELETE = 'delete';

	function add($uri,$value){
		$this->values[$uri] = $value;
	}

	function get($uri){
		
		if(($v = $this->parseURI($uri))
			&& $v['url']
			&& is_file($v['path'])
		){
			return $v['path'];
		}
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

	function save(){
		$str = '';
		foreach($this->values as $uri => $value){
			if(($v = $this->parseURI($uri))
				&& $this->checkPath($v['path'])
			){
				if($value==jpgScheme::VALUE_DELETE){
					if(file_exists($v['path'])) unlink($v['path']);
				}elseif($value){
					$filePath = (substr($value,0,1)=='/') ? $_SERVER['DOCUMENT_ROOT'].$value : $value;
					copy($filePath,$v['path']);
				}
			}
		}
	}
}
