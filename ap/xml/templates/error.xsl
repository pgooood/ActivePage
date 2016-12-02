<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" indent="no" encoding="utf-8"/>

<xsl:template match="/">
<xsl:text disable-output-escaping="yes">&lt;!DOCTYPE HTML&gt;</xsl:text>
<html>
<head>
<meta charset="utf-8"/>
<meta name="generator" content="Active Page 6.0"/>
<title>
	<xsl:text>Exception - Active Page 6.0</xsl:text>
</title>
<link href='http://fonts.googleapis.com/css?family=PT+Sans:400,400italic,700' rel='stylesheet' type='text/css'/>
<link href="css/default.css" rel="stylesheet" type="text/css"/>
<link href="css/auth.css" rel="stylesheet" type="text/css"/>
<xsl:comment><xsl:text disable-output-escaping="yes">[if lt IE 8]&gt;
&lt;link href="css/ie.css" rel="stylesheet" type="text/css"&gt;
&lt;![endif]</xsl:text></xsl:comment>
<xsl:comment><xsl:text disable-output-escaping="yes">[if lt IE 9]&gt;
&lt;script src="//html5shiv.googlecode.com/svn/trunk/html5.js"&gt;&lt;/script&gt;
&lt;![endif]</xsl:text></xsl:comment>
</head>
<body>
	<article style="position:absolute;left:50%;right:50%;top:40px;background:#fff;margin:0 -400px;padding:40px;box-shadow:3px 3px 9px #848b9c;font-size:14px;">
		<xsl:apply-templates/>
	</article>

</body>
</html>
</xsl:template>

<xsl:template match="/page/section">
	<xsl:value-of select="text()" disable-output-escaping="yes"/>
	<xsl:apply-templates select="*"/>
</xsl:template>

</xsl:stylesheet>