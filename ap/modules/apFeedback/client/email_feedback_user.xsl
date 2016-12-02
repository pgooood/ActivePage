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
<xsl:output media-type="text/html" method="html" omit-xml-declaration="yes" indent="yes" encoding="utf-8"/>
<xsl:decimal-format name="rur" decimal-separator="," grouping-separator="."/>

<!-- Шаблон письма -->
<xsl:template match="/">
<html>
<head>
	<title>Вопрос с сайта <xsl:value-of select="/email/@name" /></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>
<style type="text/css">
*{padding:0;margin:0;}
body{font-family:Arial, Helvetica, sans-serif;font-size:12px;color:#333;}
img{border:0;}
a{color:#4496d1;}
p{padding:0 0 14px 0;}

/* list */
ol,ul{overflow:auto;padding:0 0 0 25px;margin:0 0 15px;}
ul {list-style-image: url('../images/ul.png')}
ul ul,ol ul {list-style-image: url('../images/ulul.png')}
li{padding:0;margin:0 0 5px;line-height:16px;}
ol ol,
ul ul,
ol ul,
ul ol{overflow:auto;margin:0; padding-left:20px;}
li > ol, li ul {padding-top:5px;}

/* headers */
h1{font-family:Tahoma, Geneva, sans-serif;font-size:18px;font-weight:normal;color:#4496d1;text-transform:uppercase;padding:0;margin:0 0 15px;}
h2{font-family:Tahoma, Geneva, sans-serif;font-size:16px;font-weight:bold;color:#454545;padding:0;margin:0 0 15px;}
h3{font-family:Tahoma, Geneva, sans-serif;font-size:14px;font-weight:bold;color:#454545;padding:0;margin:0 0 10px;}
h1 + h1,h1 + h2,h1 + h3,
h2 + h1,h2 + h2,h2 + h3,
h3 + h1,h3 + h2,h3 + h3{margin:0 0 5px; padding:0;}

/* table */
table{background:#fefefe;margin:0 0 20px;border:0;border-collapse:collapse;border-spacing:0;}
table p{padding:0;margin:0;}
td > ul{margin-bottom:0;padding-bottom:0;}
caption{color:#666;font-family:Tahoma, Geneva, sans-serif;font-size:18px;font-weight:normal;text-transform:uppercase;padding:5px 0 0;margin:0 0 10px;text-align:left;}
th,td{border:solid #dcebf6;border-width:0 0 1px 0;padding:5px 10px;}
th {padding:7px 10px;}
th[scope='col'] {text-align: center;}
th[scope='row'] {text-align: left;}
tr.odd td{background:#dcebf6;}
th{background:#dcebf6;}
</style>
	<xsl:apply-templates />
</body>
</html>
</xsl:template>
<xsl:template match="/email">
	<div style="height:100%; width:600px; margin:20px; text-align: left;">
		
		<h1 style="font-family:Tahoma, Geneva, sans-serif;font-size:18px;font-weight:normal;color:#4496d1;text-transform:uppercase;padding:0;margin:0 0 15px;">Сообщение формы обратной связи</h1>
		<p style="padding:0 0 14px 0;">Вашe сообщение было успешно принято!</p>
		
		<xsl:if test="./field[@name='message']">
			<h2 style="font-family:Tahoma, Geneva, sans-serif;font-size:16px;font-weight:bold;color:#454545;padding:0;margin:0 0 5px;">Текст сообщения</h2>
			<div style="padding:10px 10px 10px 25px;margin:0;"><xsl:value-of select="./field[@name='message']/text()" disable-output-escaping="yes" /></div>
		</xsl:if>
		
		<hr/>
		<p style="padding:0 0 14px 0;">Письмо отправленно с сайта <a style="color:#4496d1;" href="http://{@domain}/"><xsl:value-of select="@name" /></a></p>
	</div>
</xsl:template>

</xsl:stylesheet>