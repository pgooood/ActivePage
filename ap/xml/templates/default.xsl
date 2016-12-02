<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" indent="no" encoding="utf-8"/>
<xsl:include href="structure.xsl"/>

<xsl:variable name="_sec" select="/page/structure//sec[@selected]"/>
<xsl:variable name="_ln" select="ru"/>
<xsl:variable name="_usr" select="/page/users/user[@selected]"/>

<xsl:template match="/">
<xsl:text disable-output-escaping="yes">&lt;!DOCTYPE HTML&gt;</xsl:text>
<html>
<head>
<meta charset="utf-8"/>
<meta name="generator" content="Active Page 6.0"/>
<title>
	<xsl:value-of select="$_sec/@title"/>
	<xsl:text> - Active Page 6.0</xsl:text>
</title>
<!--link href='http://fonts.googleapis.com/css?family=PT+Sans:400,400italic,700' rel='stylesheet' type='text/css'/-->
<link href="css/default.css" rel="stylesheet" type="text/css"/>
<link href="css/dropmenu.css" rel="stylesheet" type="text/css"/>
<link href="css/calendar.css" rel="stylesheet" type="text/css"/>
<xsl:comment><xsl:text disable-output-escaping="yes">[if lt IE 8]&gt;
&lt;link href="css/ie.css" rel="stylesheet" type="text/css"&gt;
&lt;![endif]</xsl:text></xsl:comment>
<xsl:comment><xsl:text disable-output-escaping="yes">[if lt IE 9]&gt;
&lt;script src="//html5shiv.googlecode.com/svn/trunk/html5.js"&gt;&lt;/script&gt;
&lt;![endif]</xsl:text></xsl:comment>
<script type="text/javascript">window.tinymce_base_url="<xsl:value-of select="/page/@base_url"/>"</script>
<script type="text/javascript" src="../tinymce/tiny_mce.js"></script>
<script type="text/javascript" src="js/cookie.js"></script>
<script type="text/javascript" src="js/todo.js"></script>
<script type="text/javascript" src="js/dropmenu.js"></script>
<script type="text/javascript" src="js/default.js"></script>
<script type="text/javascript" src="js/todo.calendar.js"></script>
</head>
<body>
<nav>
	<xsl:apply-templates select="/page/structure" mode="menu"/>
	<aside>
		<p class="domain"><a href="http://{/page/site/@domain}/" target="_blank"><xsl:value-of select="/page/site/@domain"/></a></p>
		<xsl:apply-templates select="/page/users/user[@selected]"/>
		<p class="site"><a href="http://{/page/site/@domain}/" target="_blank">смотреть страницу на сайте</a></p>
	</aside>
</nav>
<header>
	<h1>
		<xsl:if test="$_sec/parent::sec">
			<xsl:value-of select="$_sec/parent::sec/@title"/>
			<xsl:text> \\ </xsl:text>
		</xsl:if>
		<xsl:value-of select="$_sec/@title"/>
		<span><xsl:comment/></span>
	</h1>
	<img id="logo" src="images/logo.png" width="135" height="34" alt="Active Page"/>
</header>
<section>
	<article>
		<nav>
			<xsl:apply-templates select="/page/tabs"/>
		</nav>
		<div class="content">
			<xsl:apply-templates select="/page/section"/>
		</div>
	</article>
	<nav>
		<h1 class="bookmark"><span>Содержание<em></em></span></h1>
		<xsl:apply-templates select="/page/structure" mode="struct"/>
	</nav>
</section>
<footer><p>© Forumedia 2016</p></footer>
</body>
</html>
</xsl:template>

<xsl:template match="/page/users/user[@selected]">
	<p class="profile">
		<xsl:choose>
			<xsl:when test="@name"><xsl:value-of select="@name"/></xsl:when>
			<xsl:otherwise><xsl:value-of select="@login"/></xsl:otherwise>
		</xsl:choose><br/>
		<a href="?id=apUsers&amp;row={@login}&amp;action=edit">Профиль</a> - <a href="?action=logout">Выход</a>
	</p>
</xsl:template>

<xsl:template match="/page/section">
	<xsl:apply-templates select="*[not(name()='modules' or name()='crumbs')]"/>
</xsl:template>

<xsl:template match="/page/tabs">
	<ul class="bookmarks">
		<xsl:apply-templates select="tab"/>
	</ul>
</xsl:template>
<xsl:template match="/page/tabs/tab">
	<li>
		<xsl:if test="@selected">
			<xsl:attribute name="class">selected</xsl:attribute>
		</xsl:if>
		<a href="?id={$_sec/@id}&amp;md={@id}"><xsl:value-of select="@title"/></a>
		<em></em>
	</li>
</xsl:template>

<xsl:template match="/page/section//message">
	<p class="message"><xsl:value-of select="text()" disable-output-escaping="yes"/></p>
</xsl:template>

</xsl:stylesheet>