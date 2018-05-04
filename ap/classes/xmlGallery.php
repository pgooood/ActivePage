<?php
class xmlGallery {
	protected $m;
	protected $filePrefix;
	
	function __construct($module){
		$this->m = $module;
	}
	function module(){
		return $this->m;
	}
	function getFilePrefix(){
		return $this->filePrefix; 
	}
	function setFilePrefix($v){
		$this->filePrefix = $v;
	}
	static function normalizePath($v){
		$tmp = explode('/',trim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),'/'));
		array_pop($tmp);
		$rootPath = '/'.implode('/',$tmp).'/';
		$tmp = str_replace(array($rootPath,PATH_ROOT),'',$v);
		if(substr($tmp,0,1)=='/') $tmp = substr($tmp,1);
		return $tmp;
	}
	static function normalizeFilesPath($form){
		$formFields = $form->getFields('@type="file"');
		foreach($formFields as $ff)
			param($ff->getName(),xmlGallery::normalizePath(param($ff->getName())));
	}
	function getImageName($id){
		return $this->getFilePrefix().implode('_',array($this->module()->getSection()->getId(),$this->module()->getId(),$id));
	}
	function initImages($form,$isUpdate){
		$formFields = $form->getFields('@type="image"');
		$values = array();
		foreach($formFields as $ff){
			//форматы картинок
			$formats = array();
			$res = $ff->query('param');
			foreach($res as $param) $formats[] = $param->parentNode->removeChild($param);
			//получаем тэг со списком картинок, создаем объект списка
			$scheme = new xmlScheme();
			$scheme->add($ff->getURI().'/@name',$ff->getName());
			$scheme->save();
			if(($n = $scheme->getNode($ff->getURI()))
				&& $n instanceof DOMElement
			){
				$tl = new taglist($n,'img');

				//добавляем новые картинки
				$arNewImages = array();
				if($isUpdate && isset($_REQUEST[$fieldName = $ff->getName().'___new']) && is_array($_REQUEST[$fieldName])){
					$fieldNameTitle = 'title_'.$fieldName;
					foreach($_REQUEST[$fieldName] as $j => $src){
						if(is_file($path = $_SERVER['DOCUMENT_ROOT'].$src)){
							//делаем id
							$i = 1;
							while($tl->getById($id = $ff->getName()."_i$i"))$i++;
							//vdump($tl->getRootElement()->tagName);
							$arImg = array();
							$arPrev = array();
							//добавляем поля в форму
							$values[$name = $ff->getName().'_IMAGE_ID_'.$id] = $src;
							foreach($formats as $param){
								$e = $ff->getRootElement()->appendChild($param->cloneNode(true));
								$e->setAttribute('name',$name);
								$e->setAttribute('uri',str_replace('%IMG_NAME%',$this->getImageName($id),$e->getAttribute('uri')));
								if($param->hasAttribute('preview')) $arPrev[] = $e;
								else $arImg[] = $e;
							}
							

							if((count($arImg))
								&& ($src_rel = $ff->getImagePath(form::getURI($arImg[0])))
							){//добавляем в XML список картинок
								if(strpos($src_rel,PATH_ROOT)===0)
									$src_rel = substr($src_rel,strlen(PATH_ROOT));
								$arNewImages[$id] = $tl->append(array(
										'id'=>$id
										,'src'=>$src_rel
									));
							}
							//превью
							if(count($arPrev)
								&& ($src_rel = $ff->getImagePath(form::getURI($arPrev[0])))
							){
								if(strpos($src_rel,PATH_ROOT)===0)
									$src_rel = substr($src_rel,strlen(PATH_ROOT));
								if($arNewImages[$id]){
									$arNewImages[$id]->appendChild($tl->getXML()->createElement('preview',array(
										'src'=>$src_rel
									)));
								}else{
									$arNewImages[$id] = $tl->append(array(
										'id'=>$id
										,'src'=>$src_rel
									));
								}
							}

							if(isset($_REQUEST[$fieldNameTitle])
								&& isset($_REQUEST[$fieldNameTitle][$j])
								&& $_REQUEST[$fieldNameTitle][$j]
							){
								$arNewImages[$id]->setAttribute('title',mb_substr($_REQUEST[$fieldNameTitle][$j],0,127));
							}
						}
					}
				}
				//vdump($arNewImages);

				//заполняем форму текущими картинками
				$rowsToDelete = array();
				foreach($tl as $img){
					$fieldName = $ff->getName().'_IMAGE_ID_'.$img->getAttribute('id');
					if($isUpdate
						&& !isset($_REQUEST[$ff->getName()][$fieldName])
						&& !isset($arNewImages[$img->getAttribute('id')]))
					{ //определяем картинки для удаления
						$values[$fieldName] = jpgScheme::VALUE_DELETE;
						$rowsToDelete[] = $img;
					}
					foreach($formats as $param){
						if(!$isUpdate && !$param->hasAttribute('preview')) continue;
						$e = $ff->getRootElement()->appendChild($param->cloneNode(true));
						$e->setAttribute('name',$fieldName);
						$e->setAttribute('uri',str_replace('%IMG_NAME%',$this->getImageName($img->getAttribute('id')),$e->getAttribute('uri')));
						if(!$isUpdate && $img->hasAttribute('title'))
							$e->setAttribute('title',$img->getAttribute('title'));
					}
				}
				//обновляем данные
				if($isUpdate){
					//удаляем
					foreach($rowsToDelete as $e) $e->parentNode->removeChild($e);
					//обновляем тайтлы
					if(isset($_REQUEST[$fieldNameTitle = 'title_'.$ff->getName()])
						&& is_array($_REQUEST[$fieldNameTitle])
					) foreach($_REQUEST[$fieldNameTitle] as $str => $title){
						if(preg_match('/'.$ff->getName().'_IMAGE_ID_(i[0-9]+)/',$str,$m)
							&& ($e = $tl->getById($m[1]))
						){
							if($title) $e->setAttribute('title',$title);
							else $e->removeAttribute('title');
						}
					}
					//пересортировываем
					$sortOrder = isset($_REQUEST[$ff->getName().'_sort_order']) ? explode(',',$_REQUEST[$ff->getName().'_sort_order']) : array();
					foreach($sortOrder as $i => $str){
						if(preg_match('/id(i[0-9]+)/',$str,$m)
							&& ($e = $tl->getById($m[1]))
						) $tl->move($e,$i+1);
						elseif(preg_match('/new[0-9]+/',$str)
							&& ($e = array_shift($arNewImages))
						) $tl->move($e,$i+1);
					}
				}
				$tl->getXML()->save();
			}
			$ff->getRootElement()->setAttribute('target',$ff->getURI());
			$ff->getRootElement()->removeAttribute('uri');
		}
		return $values;
	}
	function updateImagesSize($form){
		$formFields = $form->getFields('@type="image"');
		foreach($formFields as $ff){
			$scheme = new xmlScheme();
			if(($n = $scheme->getNode($ff->getRootElement()->getAttribute('target')))
				&& $n instanceof DOMElement
			){
				$tl = new taglist($n,'img');
				foreach($tl as $img){
					if($img->hasAttribute('width') && $img->hasAttribute('height')) continue;
					$fieldName = $ff->getName().'_IMAGE_ID_'.$img->getAttribute('id');
					if(($res = $ff->query('param[@name="'.htmlspecialchars($fieldName).'"]'))
						&& ($e = $res->item(0))
						&& ($src_rel = $ff->getImagePath(form::getURI($e)))
					){
						list($w,$h) = getimagesize($src_rel);
						if($w && $h){
							$img->setAttribute('width',$w);
							$img->setAttribute('height',$h);
						}
						if(($prv = $tl->getXML()->query('preview',$img)->item(0))
							&& ($e = $res->item(1))
							&& ($src_rel = $ff->getImagePath(form::getURI($e)))
						){
							list($w,$h) = getimagesize($src_rel);
							if($w && $h){
								$prv->setAttribute('width',$w);
								$prv->setAttribute('height',$h);
							}
						}
					}
				}
				$tl->getXML()->save();
			}
		}
	}
}
