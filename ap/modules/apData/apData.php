<?
class apData extends module{
function run(){
	global $_struct;
	
	$defId = $_struct->getDefaultSectionId();
	$ns = $_struct->query('/structure/sec[@id="'.$this->getSection()->getId().'"]//sec[not(@readonly)]');
	$redirId = null;
	foreach($ns as $eSec){
		$id = $eSec->getAttribute('id');
		if($id == $defId)
			$redirId = $id;
		elseif(!$redirId)
			$redirId = $id;
	}
	
	if(!$redirId)
		throw new Exception('no data sections');
	
	header('Location: '.ap::getUrl(array('id' => $redirId)));
	die;
}
}