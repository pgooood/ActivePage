<?php
class content extends module{
	function run(){
		global $_out;
		$res = $this->query('*');
		$_out->addSectionContent($res);
	}
}
