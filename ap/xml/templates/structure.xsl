<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8"/>

<!-- Верхнее меню -->
<xsl:template match="structure" mode="menu">
	<ul id="menu">
		<xsl:apply-templates select="sec" mode="menu"/>
	</ul>
</xsl:template>

<xsl:template match="structure/sec" mode="menu">
	<li>
		<xsl:variable name="selected" select="$_sec/ancestor-or-self::sec/@id=@id"/>
		<xsl:variable name="children" select="not(@id='apStruct' or @id='apData') and sec"/>
		<xsl:if test="$children or $selected">
			<xsl:attribute name="class">
				<xsl:if test="$children">parent </xsl:if>
				<xsl:if test="$selected">selected</xsl:if>
			</xsl:attribute>
		</xsl:if>
		<xsl:choose>
			<xsl:when test="@readonly">
				<span><xsl:value-of select="@title"/></span>
			</xsl:when>
			<xsl:otherwise>
				<xsl:variable name="id"><xsl:choose>
					<!-- при клике на структуру переносим в параллельный раздел структуры -->
					<xsl:when test="@id='apStruct' and $_sec/ancestor::sec[@id='apData']">
						<xsl:text>_s_</xsl:text>
						<xsl:value-of select="$_sec/@id"/>
					</xsl:when>
					<!-- при клике на данные переносим в параллельный раздел данных -->
					<xsl:when test="@id='apData' and $_sec/ancestor::sec[@id='apStruct']">
						<xsl:value-of select="substring-after($_sec/@id,'_s_')"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="@id"/>
					</xsl:otherwise>
				</xsl:choose></xsl:variable>
				<a href="?id={$id}"><xsl:value-of select="@title"/></a>
			</xsl:otherwise>
		</xsl:choose>
		<xsl:if test="$children">
			<div class="sub">
				<ul>
					<xsl:apply-templates select="sec" mode="menu"/>
				</ul>
			</div>
		</xsl:if>
	</li>
</xsl:template>

<xsl:template match="structure/sec/sec" mode="menu">
	<li>
		<xsl:if test="$_sec/ancestor-or-self::sec/@id=@id">
			<xsl:attribute name="class">selected</xsl:attribute>
		</xsl:if>
		<a href="?id={@id}"><xsl:value-of select="@title"/></a>
	</li>
</xsl:template>

<!-- Левое меню -->
<xsl:template match="structure" mode="struct">
	<xsl:apply-templates select="$_sec/ancestor-or-self::sec[parent::structure]" mode="struct"/>
</xsl:template>

<xsl:template match="structure/sec" mode="struct">
	<h2><xsl:value-of select="@title"/></h2>
	<xsl:if test="sec">
		<dl id="sidemenu" class="dropmenu">
			<xsl:choose>
				<xsl:when test="$_sec">
					<xsl:apply-templates select="sec" mode="struct"/>
				</xsl:when>
			</xsl:choose>
		</dl>
	</xsl:if>
</xsl:template>

<xsl:template match="structure/sec//sec" mode="struct">
	<dt>
		<xsl:choose>
			<xsl:when test="$_sec/@id=@id">
				<xsl:attribute name="class">selected</xsl:attribute>
			</xsl:when>
			<xsl:when test="$_sec/ancestor::sec/@id=@id">
				<xsl:attribute name="class">open</xsl:attribute>
			</xsl:when>
		</xsl:choose>
		<xsl:choose>
			<xsl:when test="@readonly and not(contains(@id,'_s_'))">
				<span><xsl:value-of select="@title"/></span>
			</xsl:when>
			<xsl:otherwise>
				<a href="?id={@id}"><xsl:value-of select="@title"/></a>
			</xsl:otherwise>
		</xsl:choose>
	</dt>
	<xsl:if test="sec">
		<dd>
			<dl>
				<xsl:apply-templates select="sec" mode="struct"/>
			</dl>
		</dd>
	</xsl:if>
</xsl:template>

</xsl:stylesheet>