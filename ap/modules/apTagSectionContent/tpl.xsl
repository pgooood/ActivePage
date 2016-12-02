<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="/page/section/form//field[@type='text' and @name='tag']" priority="1">
	<xsl:variable name="id" select="@name"/>
	<div class="field text">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/></label>
		<div class="hint">
			<input type="text" name="{@name}" id="{$id}" maxlength="255" size="40" autocomplete="off" value="{text()}"/>
			<ul id="{$id}_hint" style="display:none;"></ul>
		</div>
	</div>
	<script>
todo.onload(function(){
	var inp=todo.get('<xsl:value-of select="$id"/>')
		,ulHint=todo.get('<xsl:value-of select="$id"/>_hint')
		,hintId
		,closeEventIndex
		,showHint=function(xml){
			var ns=xml.getElementsByTagName('tag'),li;
			ulHint.innerHTML='';
			if(ns.length){
				for(var i=0;i&lt;ns.length;i++){
					li=ulHint.appendChild(document.createElement('li'));
					li.innerHTML=ns[i].firstChild.data;
					li.onclick=function(){
						inp.value=this.innerHTML;
						ulHint.style.display='none';
					}
				};
				ulHint.style.display='';
				closeEventIndex=todo.setEvent('click',document.body,function(){
					ulHint.style.display='none';
					todo.unsetEvent('click',document.body,closeEventIndex);
				});
			}else ulHint.style.display='none';
		};
	if(inp)inp.onkeyup=function(){
		if(!hintId){
			hintId=window.setTimeout(function(){
				todo.ajax(
					window.location.pathname+window.location.search.replace(/action=[^&amp;]+/,'')
					,function(text,xml){
						if(xml)showHint(xml);
						hintId=null;
					}
					,{
						action:'taghint'
						,str:inp.value
					}
				);
			},300);
		};
	};
});
	</script>
</xsl:template>

</xsl:stylesheet>