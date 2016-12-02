<?
class apSectionTemplate extends module{
function getRow(){
	if($v = param('row')) return $v;
}
function getMessage(){
	switch(param('mess')){
		case 'delete_ok':
			return _('Удаление выполнено успешно');
		case 'delete_fail':
			return _('Удаление не выполнено');
		case 'not_found':
			return _('Раздел не найден');
	}
}
function redirect($mess = null){
	$param = array();
	if($mess) $param['mess'] = $mess;
	header('Location: '.ap::getUrl($param));
	die;
}
function getRowList(){
	if($list_element = $this->query('rowlist')->item(0)){
		$tl = $this->getPackages();
		$rl = new rowlist($list_element,$tl->getNum(),param('page'));
		$s = $rl->getStartIndex();
		$f = $rl->getFinishIndex();
		$i = 0;
		foreach($tl as $package){
			if($i<$s) continue;
			elseif($i>$f) break;
			$rl->addRow($package->getAttribute('id'),array('sort' => $i+1,'title' => $package->getAttribute('title')));
			$i++;
		}
		$rl->setFormAction(preg_replace('/&?mess=[\w_]*/','',$_SERVER['REQUEST_URI']));
		return $rl;
	}
}
function run(){
	global $_out;
	if(ap::isCurrentModule($this)){
		/* локализация */
		ap::translate($this->getSection()->getXML(),array(
			'//@title',
			'//field[@label]/@label',
			'//button[@value]/@value',
			'//rowlist/col[@header]/@header',
		),PATH_MODULE.__CLASS__.'/translate.php');
		ap::addMessage($this->getMessage());
		$row = $this->getRow();
		switch($action = param('action')){
			case 'delete':
				if($row && ($tl = $this->getPackages())){
					if(!is_array($row)) $row = array($row);
					foreach($row as $v)
						if($e = $tl->getById($v)) $tl->remove($e);
					$tl->getXML()->save();
					$this->redirect('delete_ok');
				}else $this->redirect('delete_fail');
				break;
			default:
				if($rl = $this->getRowList()){
					$_out->elementIncludeTo($rl->getRootElement(),'/page/section');
				}
		}
	}
}
/**
* Создание шаблона
*/
static function getDataXML(){
	return new xml(PATH_DATA.__CLASS__.'.xml','section',false);
}
static function getPackages(){
	$xml = apSectionTemplate::getDataXML();
	if($m = $xml->query('/section/modules/module[@name="apSectionTemplate"]')->item(0)){
		if(!($e = $xml->query('packages',$m)->item(0)))
			$e = $m->appendChild($xml->createElement('packages'));
		if($e) return new taglist($e,'package');
	}
}
static function createPackage($id,$title = null){
	global $_struct;
	if(($tl = apSectionTemplate::getPackages())
		&& ($apsec = $_struct->getSection($id))
		&& ($clsec = ap::getClientSection($id))
		&& ($pckg = $tl->append(array('id' => $tl->generateId('p'), 'title' => $title ? $title : $apsec->getTitle())))
	){
		if($e = $apsec->getXML()->de()){
			$e = $pckg->appendChild($tl->getXML()->importNode($e));
			$e->setAttribute('_ap','_ap');
		}
		if($e = $clsec->getXML()->de()){
			$e = $pckg->appendChild($tl->getXML()->importNode($e));
			$e->setAttribute('_cl','_cl');
		}
		if($v = $clsec->getClass())
			$pckg->setAttribute('class',$v);
		$tl->getXML()->save();
		return $pckg;
	}
}
static function applyTemplate($id_sec,$id_pckg){
	global $_struct;
	$tl = apSectionTemplate::getPackages();
	if(($tl = apSectionTemplate::getPackages())
		&& ($pckg = $tl->getById($id_pckg))
		&& ($apsec = $_struct->getSection($id_sec))
		&& ($clsec = ap::getClientSection($id_sec))
	){
		if($v = $pckg->getAttribute('class')){
			$apsec->setClass($v);
			$clsec->setClass($v);
			$struct = new xml($clsec->getElement());
			$struct->save();
		}
		$ar = array('_ap' => $apsec->getXML(false),
					'_cl' => $clsec->getXML());
		foreach($ar as $attr => $xml)
			if($e = $tl->getXML()->query('section[@'.$attr.']',$pckg)->item(0)){
				$xml->removeChild();
				if($e = $xml->appendChild($xml->importNode($e))){
					$e->removeAttribute($attr);
					$xml->save();
				}
			}
	}
}
}
?>