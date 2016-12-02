<?php
class uploads extends articles{
function getListXML($tagName){
	if($xml = parent::getListXML($tagName)){
		$ns = $xml->query('/*/row/article');
		foreach($ns as $n){
			if(($path = xml::getElementText($n)) && is_file($path)){
				$n->parentNode->appendChild($xml->createElement('file',array(
						'path' => $path
						,'size' => $this->file_size(filesize($path))
						,'ext' => strtolower(pathinfo($path,PATHINFO_EXTENSION))
					)));
			}
			$n->parentNode->removeChild($n);
		}
	}
	return $xml;
}
function getListTable(){
    if($tb=parent::getListTable()){
        $tb->setQueryFields(array('title','article'));
    }
	return $tb;
}
function getDetailXML($tagName,$row){
}
function file_size($size){
	$filesizename = array(" Б", " Кб", " Мб", " Гб", " TB", " PB", " EB", " ZB", " YB");
	return $size ? number_format(round($size/pow(1024, ($i = floor(log($size, 1024)))), 2), 2, ',', ' ') . $filesizename[$i] : '0 Bytes';
}
}