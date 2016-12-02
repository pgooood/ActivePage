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

<xsl:template match="/page/section/form//field[contains(@check,'new_id')]" mode="fieldcheck">
	if(this['<xsl:value-of select="@name"/>'].value == ''){
		alert('<xsl:text lang="lang">Заполните поле ID!</xsl:text>');return false;
	};
	if(!this['<xsl:value-of select="@name"/>']._chars){
		if(this['<xsl:value-of select="@name"/>'].value.length &lt; 3){
			alert('<xsl:text lang="lang">ID должен содержать 3 и более символов!</xsl:text>');return false;
		};
		alert('<xsl:text lang="lang">ID содержит недопустимые символы!</xsl:text>');return false;
	};
	if(!this['<xsl:value-of select="@name"/>']._new_id){
		alert('<xsl:text lang="lang">Раздел с таким ID уже существует!</xsl:text>');return false;
	};
</xsl:template>

<xsl:template match="/page/section/form//field[@type='section_test_id']">
	<div class="field text">
		<label for="{@name}">*<xsl:value-of select="@label"/></label>
		<input type="text" name="{@name}" id="{@name}" maxlength="255" value="{text()}">
			<xsl:if test="@size">
				<xsl:attribute name="size"><xsl:value-of select="@size"/></xsl:attribute>
			</xsl:if>
		</input>
	</div>
<script type="text/javascript">
<xsl:text disable-output-escaping="yes">
todo.onload(function(){
	var input=todo.get('</xsl:text><xsl:value-of select="@name"/><xsl:text disable-output-escaping="yes">'),
		testid=function(){
			if(this.value.match(/^[a-z]{1}[a-z0-9_-]{2,50}$/i)){
				this._chars = true;
				todo.ajax('',function(o){return function(text,xml){
					o._new_id=text=='1';
					o.style.border='1px solid '+(o._new_id?'green':'red');
				};}(this),{
					'id':'</xsl:text><xsl:value-of select="$_sec/@id"/><xsl:text disable-output-escaping="yes">',
					'md':'</xsl:text><xsl:value-of select="/page/section/@module"/><xsl:text disable-output-escaping="yes">',
					'action':'ajax',
					'isset':this.value
				});
			}else{
				this._chars=false;
				this.style.border='1px solid red';
			}
		};
	if(input){
		testid.call(input);
		if(input.addEventListener){
			input.addEventListener('change',testid,false);
			input.addEventListener('keyup',testid,false);
		}else{
			input.attachEvent('onchange',testid);
			input.attachEvent('onkeyup',testid);
		};
	}
});
</xsl:text>
</script>
</xsl:template>

<xsl:template match="/page/section/form//field[contains(@check,'new_url')]" mode="fieldcheck">
	if(!this['<xsl:value-of select="@name"/>']._chars){
		if(this['<xsl:value-of select="@name"/>'].value.length &lt; 2){
			alert('<xsl:text lang="lang">URL должен содержать 2 и более символов!</xsl:text>');return false;
		};
		alert('<xsl:text lang="lang">URL содержит недопустимые символы!</xsl:text>');return false;
	};
	if(this['<xsl:value-of select="@name"/>'].value &amp;&amp; !this['<xsl:value-of select="@name"/>']._new_url){
		alert('<xsl:text lang="lang">Раздел с таким URL уже существует!</xsl:text>');return false;
	};
</xsl:template>

<xsl:template match="/page/section/form//field[@type='section_test_url']">
	<div class="field text">
		<label for="{@name}"><xsl:value-of select="@label"/></label>
		<input type="text" name="{@name}" id="{@name}" maxlength="255" value="{text()}">
			<xsl:if test="@size">
				<xsl:attribute name="size"><xsl:value-of select="@size"/></xsl:attribute>
			</xsl:if>
		</input>
	</div>
<script type="text/javascript">
<xsl:text disable-output-escaping="yes">
todo.onload(function(){
	var input=todo.get('</xsl:text><xsl:value-of select="@name"/><xsl:text disable-output-escaping="yes">'),
		testurl=function(){
			this._chars=true;
			if(!this.value){
				this.style.border='1px solid #abc0d5';
				this.style.borderBottom=0;
				this.style.borderRight=0;
				return;
			};
			if(this.value.match(/^[a-z]{1}[a-z0-9_-]{1,50}$/i)){
				todo.ajax('',function(o){return function(text,xml){
					o._new_url=text=='1';
					o.style.border='1px solid '+(o._new_url?'green':'red');
				};}(this),{
					'id':'</xsl:text><xsl:value-of select="$_sec/@id"/><xsl:text disable-output-escaping="yes">',
					'md':'</xsl:text><xsl:value-of select="/page/section/@module"/><xsl:text disable-output-escaping="yes">',
					'action':'ajax',
					'isset':this.value
				});
			}else{
				this._chars=false;
				this.style.border='1px solid red';
			}
		};
	if(input){
		testurl.call(input);
		if(input.addEventListener){
			input.addEventListener('change',testurl,false);
			input.addEventListener('keyup',testurl,false);
		}else{
			input.attachEvent('onchange',testurl);
			input.attachEvent('onkeyup',testurl);
		};
	}
});</xsl:text>
</script>
</xsl:template>



<xsl:template match="/page/section/form//field[@type='sectionbyid']">
	<div class="field select">
		<label for="{@name}"><xsl:call-template name="required"/><xsl:value-of select="@label"/>:</label>
		<select name="{@name}" id="{@name}"><xsl:comment/></select>
	</div>
<script type="text/javascript">
<xsl:text disable-output-escaping="yes">
todo.onload(function(){
	var sel_parent = todo.get('parent'),
		sel_child = todo.get('</xsl:text><xsl:value-of select="@name"/><xsl:text disable-output-escaping="yes">'),
		search = function(){
			while(sel_child.hasChildNodes())sel_child.removeChild(sel_child.firstChild);
			todo.ajax('',function(text,xml){
				if(!xml){
					alert(text);
					return;
				}
				var ns=xml.getElementsByTagName('sec'),sel;
				for(var i=0;i&lt;ns.length;i++)sel_child.appendChild(new Option('</xsl:text><xsl:text lang="lang">ПЕРЕД</xsl:text><xsl:text disable-output-escaping="yes"> '+ns[i].getAttribute('title'),ns[i].getAttribute('id')));
				sel_child.appendChild(new Option('</xsl:text><xsl:text lang="lang">ПОСЛЕДНИЙ</xsl:text><xsl:text disable-output-escaping="yes">','',true,true));
			},{'id':'</xsl:text><xsl:value-of select="$_sec/@id"/><xsl:text disable-output-escaping="yes">',
				'md':'</xsl:text><xsl:value-of select="/page/section/@module"/><xsl:text disable-output-escaping="yes">',
				'action':'ajax',
				'parent':sel_parent.value
			});
		};
	if(sel_parent &amp;&amp; sel_child){
		if(sel_parent.addEventListener)sel_parent.addEventListener('change',search);
		else sel_parent.attachEvent('onchange',search);
	};
	search();
});
</xsl:text>
</script>
</xsl:template>


</xsl:stylesheet>