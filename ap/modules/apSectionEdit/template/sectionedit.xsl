<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet  [
	<!ENTITY nbsp   "&#160;">
	<!ENTITY copy   "&#169;">
	<!ENTITY reg    "&#174;">
	<!ENTITY trade  "&#8482;">
	<!ENTITY mdash  "&#8212;">
	<!ENTITY ldquo  "&#8220;">
	<!ENTITY rdquo  "&#8221;"> 
	<!ENTITY pound  "&#163;">
	<!ENTITY yen    "&#165;">
	<!ENTITY euro   "&#8364;">
]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8"/>

<xsl:template match="/page/section/form//field[@type='section_test_id']">
	<div class="field text">
		<label for="{@name}">*<xsl:value-of select="@label"/></label>
		<input type="text" name="{@name}" id="{@name}" maxlength="255" value="{text()}" readonly="readonly" style="background:#EEE">
			<xsl:if test="@size">
				<xsl:attribute name="size"><xsl:value-of select="@size"/></xsl:attribute>
			</xsl:if>
		</input>
	</div>
</xsl:template>
<xsl:template match="/page/section/form//field[@type='sectionbyid']">
	<div class="field select">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/>:</label>
		<select name="{@name}" id="{@name}"><xsl:comment/></select>
	</div>
<script type="text/javascript">
<xsl:text disable-output-escaping="yes">
todo.onload(function(){
	var now_id='</xsl:text><xsl:value-of select="$_sec/@id"/><xsl:text disable-output-escaping="yes">'.substr(3),
		sel_parent=todo.get('parent'),
		sel_child=todo.get('</xsl:text><xsl:value-of select="@name"/><xsl:text disable-output-escaping="yes">'),
		seatch=function(id){
			while(sel_child.hasChildNodes())sel_child.removeChild(sel_child.firstChild);
			todo.ajax('',function(text,xml){
				if(!xml)return;
				var ns=xml.getElementsByTagName('sec'),sel;
				for(var i=0;i&lt;ns.length;i++){
					if(now_id!=ns[i].getAttribute('id'))sel_child.appendChild(new Option('ПЕРЕД '+ns[i].getAttribute('title'),ns[i].getAttribute('id'),id.toString()==sel,id.toString()==sel));
					sel=ns[i].getAttribute('id');
				};
				option=sel_child.appendChild(todo.create('option',{'value':''},'ПОСЛЕДНИЙ'));
				option.selected=id.toString()==sel;
			},{'id':'</xsl:text><xsl:value-of select="$_sec/@id"/><xsl:text disable-output-escaping="yes">',
				'md':'</xsl:text><xsl:value-of select="/page/section/@module"/><xsl:text disable-output-escaping="yes">',
				'action':'ajax',
				'parent':sel_parent.value
			});
		};
	if(sel_parent &amp;&amp; sel_child){
		if(sel_parent.addEventListener)sel_parent.addEventListener('change',seatch);
		else sel_parent.attachEvent('onchange',seatch);
	};
	seatch('</xsl:text><xsl:value-of select="text()"/><xsl:text disable-output-escaping="yes">');
});
</xsl:text>
</script>
</xsl:template>

</xsl:stylesheet>