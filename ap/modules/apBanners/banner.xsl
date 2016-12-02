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

<xsl:template match="/page/section/form//field[@type='banner']">
	<xsl:variable name="fieldName" select="@name"/>
	<div class="field file">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/></label>
		<input type="button" class="add" value="Выбрать баннер" id="editimage{$fieldName}"/>
		<textarea id="textarea{$fieldName}" style="display:none;"><xsl:comment/></textarea>
		<input type="hidden" id="{$fieldName}" name="{$fieldName}" value="{text()}"/>
	</div>
	<div class="fieldset gallery" id="fieldset_{$fieldName}" style="display:none;">
		<h4 class="legend">Управление баннером</h4>
		<div id="banner{$fieldName}"></div>
		<input type="button" value="Удалить баннер" class="delete" id="bannerDeleteButton{$fieldName}" />
	</div>
	<script type="text/javascript" src="modules/apBanners/swfobject.js"></script>
	<script type="text/javascript">
<xsl:text disable-output-escaping="yes">
todo.onload(function(){
editor('textarea</xsl:text><xsl:value-of select="$fieldName"/><xsl:text>');
var input=todo.get('editimage</xsl:text><xsl:value-of select="$fieldName"/><xsl:text>')
	,deleteButton=todo.get('bannerDeleteButton</xsl:text><xsl:value-of select="$fieldName"/><xsl:text>')
	,initBanner=function(url){
		var field=todo.get('fieldset_</xsl:text><xsl:value-of select="$fieldName"/><xsl:text>')
			,banner=todo.get('banner</xsl:text><xsl:value-of select="$fieldName"/><xsl:text>');
		while(banner.firstChild)banner.removeChild(banner.firstChild);
		field.style.display='none';
		if(!url){
			todo.get('</xsl:text><xsl:value-of select="$fieldName"/><xsl:text>').value='';
			return;
		};
		url = '../'+url;
			banner=banner.appendChild(todo.create('div'));
			var type=url.replace(/^.+\.(gif|jpg|swf)$/i,"$1");
			switch(type.toLowerCase()){
				case 'gif':case 'jpg':
					field.style.display='block';
					banner.appendChild(todo.create('img',{'width':'</xsl:text><xsl:value-of select="@width"/><xsl:text>px','height':'</xsl:text><xsl:value-of select="@height"/><xsl:text>px','src':url}));
					break;
				case 'swf':
					todo.ajax(window.location.pathname+window.location.search+'&amp;action=bannersize&amp;path='+encodeURIComponent(url),function(text,xml){
						if(!xml)return;
						var size=xml.getElementsByTagName('size')[0];
						field.style.display='block';
						swfobject.embedSWF(url, banner,size.getAttribute('width'),size.getAttribute('height'),10);
					});
					break;
			}
		
	};
deleteButton.onclick=function(){
	if(confirm('Удалить баннер'))initBanner();
};
input.onclick=function(){
	var fs=todo.get('fieldset_</xsl:text><xsl:value-of select="$fieldName"/><xsl:text disable-output-escaping="yes">');
	tinyMCE.activeEditor.windowManager.open({
		file:'uploader.php?opener=tinymce&amp;type=media',
		title:'Active Page File Manager',
		width:700,height:500,
		resizable:"yes",inline:true,close_previous:"no",popup_css:false
	},{callback:function(url){
		var short_url = url.replace(/^.+(userfiles.+)$/i,"$1");
		document.getElementById('</xsl:text><xsl:value-of select="$fieldName"/><xsl:text>').value=short_url;
		initBanner(short_url);
	}});
};
initBanner('</xsl:text><xsl:value-of select="text()"/><xsl:text>');
});
</xsl:text>
	</script>
</xsl:template>


</xsl:stylesheet>