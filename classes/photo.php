<?
class photo extends content{
function announce($tagname,$sort = null,$size = null,$parent = null){
	global $_out;
	$xml = new xml(null,$tagname,false);
	$ns = $this->query('.//img');
	if($ns->length){
		if($sort=='desc'){
			$c = 0;
			for($i=$ns->length-1; $i>=0; $i--){
				$xml->de()->appendChild($xml->importNode($ns->item($i)));
				$c++;
				if($size && $c==$size) break;
			}
		}else{
			foreach($ns as $i => $e){
				if($i==$size) break;
				$xml->de()->appendChild($xml->importNode($e));
			}
		}
		$_out->xmlIncludeTo($xml,$parent);
	}
}
}
