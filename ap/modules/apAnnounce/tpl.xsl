<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:template match="form//field[@name='module']" priority="1">
	<div class="field select">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/>:</label>
		<select name="{@name}" id="{@name}">
			<xsl:apply-templates/>
		</select>
	</div>
<script type="application/javascript">
<xsl:text>
todo.onload(function(){
	var selSection=todo.get('</xsl:text><xsl:value-of select="@sectionFieldName"/><xsl:text>')
		,selModule=todo.get('</xsl:text><xsl:value-of select="@name"/><xsl:text>')
		,defVal='</xsl:text><xsl:value-of select="@value"/><xsl:text>';
	selSection.onchange=function(){
		selModule.options.length=0;
		selModule.options[0]=new Option('Загрузка...','',true);
		selSection.disabled=
		selModule.disabled=true;
		todo.ajax('?id=</xsl:text><xsl:value-of select="$_sec/@id"/><xsl:text>&amp;md=</xsl:text><xsl:value-of select="/page/section/@module"/><xsl:text>&amp;row='+this.form.row.value+'&amp;action=edit'
			,function(text,xml){
					selSection.disabled=
					selModule.disabled=false;
					selModule.options.length=0;
					var modules=xml.getElementsByTagName('module'),n;
					for(var i=0;i&lt;modules.length;i++){
						m=modules[i];
						selModule.options[i]=new Option(m.getAttribute('name')
							+(m.getAttribute('title')?' ('+m.getAttribute('title')+')':''),m.getAttribute('id'),defVal&amp;&amp;defVal==m.getAttribute('id'));
					};
					defVal=null;
				}
			,{'section':this.options[this.selectedIndex].value}
			,'post'
		);
	};
	selSection.onchange();
});
</xsl:text>
</script>
</xsl:template>

</xsl:stylesheet>