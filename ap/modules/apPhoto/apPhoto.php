<?
class apPhoto extends apContent{
function settings($action){
	if(($xml = $this->getDataXML())
		&& ($e = $xml->getElementById('content_form_settings'))
	){
		$form = new form($e);
		//размер превью
		if(($ffh = $form->getField('previewSizeH'))
			&& ($ffv = $form->getField('previewSizeV'))
			&& ($ffMaxPrev = $form->getField('previewSizeMax'))
			&& ($ffMaxImg = $form->getField('imgSizeMax'))
		){
			$res = $this->query('form//field[@type="image"]/param[@preview]');
			foreach($res as $e){
				$ffi = new formImageField($e);
				switch($action){
					case 'update':
					case 'apply_update':
						$ffi->setPreviewSize(intval(param('previewSizeH')),intval(param('previewSizeV')),intval(param('previewSizeMax')));
						$ffi->getXML()->save();
						break;
					default:
						if(is_array($s = $ffi->getPreviewSize())){
							$ffh->setValue($s['width']);
							$ffv->setValue($s['height']);
							$ffMaxPrev->setValue($s['max']);
						}
						break;
				}
			}
			$res = $this->query('form//field[@type="image"]/param[not(@preview)]');
			foreach($res as $e){
				$ffi = new formImageField($e);
				switch($action){
					case 'update':
					case 'apply_update':
						$ffi->setPreviewSize(null,null,intval(param('imgSizeMax')));
						$ffi->getXML()->save();
						break;
					default:
						if(is_array($s = $ffi->getPreviewSize()))
							$ffMaxImg->setValue($s['max']);
						break;
				}
			}
		}
	}
	return parent::settings($action);
}
}
