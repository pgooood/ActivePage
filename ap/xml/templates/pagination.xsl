<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<!-- навигация по страницам -->
<xsl:template name="page_navigator">
	<xsl:param name="numpages"/>
	<xsl:param name="page"/>
	<xsl:param name="url"/>
	<xsl:param name="anchor"/>
	<xsl:if test="$numpages &gt; 1">
		<nav class="pagination">
			<h1>Страницы:</h1>
			<xsl:choose>
				<xsl:when test="$page - 2 &gt; 2">
					<xsl:call-template name="pages">
						<xsl:with-param name="i" select="1"/>
						<xsl:with-param name="numpages" select="1"/>
						<xsl:with-param name="selected" select="$page"/>
						<xsl:with-param name="url" select="$url"/>
						<xsl:with-param name="anchor" select="$anchor"/>
					</xsl:call-template>
					<span>...</span>
					<xsl:call-template name="pages">
						<xsl:with-param name="i" select="$page - 2"/>
						<xsl:with-param name="numpages" select="$page"/>
						<xsl:with-param name="selected" select="$page"/>
						<xsl:with-param name="url" select="$url"/>
						<xsl:with-param name="anchor" select="$anchor"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:otherwise>
					<xsl:call-template name="pages">
						<xsl:with-param name="i" select="1"/>
						<xsl:with-param name="numpages" select="$page"/>
						<xsl:with-param name="selected" select="$page"/>
						<xsl:with-param name="url" select="$url"/>
						<xsl:with-param name="anchor" select="$anchor"/>
					</xsl:call-template>
				</xsl:otherwise>
			</xsl:choose>
		
			<xsl:choose>
				<xsl:when test="$page + 2 &lt; $numpages - 1">
					<xsl:call-template name="pages">
						<xsl:with-param name="i" select="$page + 1"/>
						<xsl:with-param name="numpages" select="$page + 2"/>
						<xsl:with-param name="selected" select="$page"/>
						<xsl:with-param name="url" select="$url"/>
						<xsl:with-param name="anchor" select="$anchor"/>
					</xsl:call-template>
					<span>...</span>
					<xsl:call-template name="pages">
						<xsl:with-param name="i" select="$numpages"/>
						<xsl:with-param name="numpages" select="$numpages"/>
						<xsl:with-param name="selected" select="$page"/>
						<xsl:with-param name="url" select="$url"/>
						<xsl:with-param name="anchor" select="$anchor"/>
					</xsl:call-template>
				</xsl:when>
				<xsl:when test="$page &lt; $numpages">
					<xsl:call-template name="pages">
						<xsl:with-param name="i" select="$page + 1"/>
						<xsl:with-param name="numpages" select="$numpages"/>
						<xsl:with-param name="selected" select="$page"/>
						<xsl:with-param name="url" select="$url"/>
						<xsl:with-param name="anchor" select="$anchor"/>
					</xsl:call-template>
				</xsl:when>
			</xsl:choose>
		</nav>
	</xsl:if>
</xsl:template>
<xsl:template name="pages">
	<xsl:param name="i"/>
	<xsl:param name="numpages"/>
	<xsl:param name="selected"/>
	<xsl:param name="url"/>
	<xsl:param name="anchor"/>
	<xsl:if test="$i &lt;= $numpages">
		<a>
			<xsl:attribute name="href">
				<xsl:value-of select="$url"/>
				<xsl:if test="$i &gt; 1">&amp;page=<xsl:value-of select="$i"/></xsl:if>
				<xsl:if test="$anchor">#<xsl:value-of select="$anchor"/></xsl:if>
			</xsl:attribute>
			<xsl:if test="$selected=$i">
				<xsl:attribute name="class">selected</xsl:attribute>
			</xsl:if>
			<xsl:value-of select="$i" />
		</a>
		<xsl:call-template name="pages">
			<xsl:with-param name="i" select="$i + 1"/>
			<xsl:with-param name="numpages" select="$numpages"/>
			<xsl:with-param name="selected" select="$selected"/>
			<xsl:with-param name="url" select="$url"/>
			<xsl:with-param name="anchor" select="$anchor"/>
		</xsl:call-template>
	</xsl:if>
</xsl:template>

</xsl:stylesheet>