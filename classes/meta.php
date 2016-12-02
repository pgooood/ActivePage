<?php
class meta extends module{
	function run(){
		global $_out;
		if($_out->isMetaWritable('title') && ($v = $this->evaluate('string(title)')))
			$_out->setMeta('title',$v);
		if($_out->isMetaWritable('keywords') && ($v = $this->evaluate('string(keywords)')))
			$_out->setMeta('keywords',$v);
		if($_out->isMetaWritable('description') && ($v = $this->evaluate('string(description)')))
			$_out->setMeta('description',$v);
	}
}