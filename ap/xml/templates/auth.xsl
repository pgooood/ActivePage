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
	<xsl:value-of select="/page/site/@domain"/>
	<xsl:text> - Active Page 6.0</xsl:text>
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
<script type="text/javascript" src="js/todo.js"></script>
<script type="text/javascript">
todo.onload(function(){todo.get('login').focus();});
</script>
</head>
<body>
	<div id="auth">
		<header>
			<h1><img id="logo" src="images/logo.png" width="135" height="34" alt="Active Page"/></h1>
			<nav><a href="http://{/page/site/@domain}/"><xsl:value-of select="/page/site/@domain"/></a></nav>
		</header>
		<article>
			<form id="form_auth" action="{/page/@url}" method="post">
				<input type="hidden" name="action" value="login"/>
				<div><label for="login">Логин:</label>
					<input type="text" name="login" id="login" maxlength="30" size="20"/></div>
				<div><label for="pass">Пароль:</label>
					<input type="password" name="pass" id="pass" maxlength="30" size="20"/></div>
				<div><input type="submit" value="Войти в систему" class="ok"/></div>
			</form>
		</article>
	</div>
</body>
</html>
</xsl:template>

</xsl:stylesheet>