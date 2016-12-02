<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8"/>

<xsl:template match="/page/section/form//field[@name='alias']" priority="1">
	<div class="field text">
		<label for="{@name}">*<xsl:value-of select="@label"/></label>
		<input type="{@type}" name="{@name}" id="{@name}" maxlength="63" value="{text()}">
			<xsl:if test="@size">
				<xsl:attribute name="size"><xsl:value-of select="@size"/></xsl:attribute>
			</xsl:if>
		</input>
		<xsl:value-of select="@titleField"/>
		<script type="application/javascript">
		<xsl:text>
todo.onload(function(){
	var e=todo.get('</xsl:text><xsl:value-of select="@sectionTitleField"/><xsl:text>');
	e.onkeyup=e.onchange=function(){
		this.form['</xsl:text><xsl:value-of select="@name"/><xsl:text>'].value=function(str){
			var r='',k,L={'а':'a','б':'b','в':'v','г':'g','д':'d','е':'e','ё':'yo','ж':'zh','з':'z','и':'i','й':'y','к':'k','л':'l','м':'m','н':'n','о':'o','п':'p','р':'r','с':'s','т':'t','у':'u','ф':'f','х':'kh','ц':'ts','ч':'ch','ш':'sh','щ':'sch','ъ':'','ы':'y','ь':"'",'э':'e','ю':'yu','я':'ya',' ':'_'};
			for(k in L)r+=k;
			r=new RegExp('['+r+']','g');
			k=function(a){return a in L?L[a]:'';};
			return str.toLowerCase().replace(r,k).replace(/[^a-zA-Z0-9_]/g,'');
		}(this.value);
	}
});</xsl:text></script>
	</div>
</xsl:template>
<xsl:template match="/page/section/form//field[contains(@check,'sectionId')]" mode="fieldcheck" priority="10">if(!this['<xsl:value-of select="@name"/>'].value.match(/^[a-z]{1}[a-z0-9_-]{2,128}$/i)){alert('Поле "<xsl:value-of select="@label"/>" должно содержать не менее трех латинских символов\n в нижнем регистре, без пробелов и не должно совпадать с сылками других разделов');return false;};</xsl:template>

</xsl:stylesheet>