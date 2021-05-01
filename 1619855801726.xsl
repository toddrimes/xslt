<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="2.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                   xmlns:media="http://search.yahoo.com/mrss/"
                   exclude-result-prefixes="media">

    <xsl:output method="xml" encoding="UTF-8" indent="yes" />
    <xsl:template name="rss" match="/">
        <rss xmlns:media="'http://search.yahoo.com/mrss/'">
            <xsl:for-each select="rss">
                <xsl:attribute name="version">
                    <xsl:value-of select="string(number(string(@version)))" />
                </xsl:attribute>
                <xsl:apply-templates select="@* | node()"/>
            </xsl:for-each>
        </rss>
    </xsl:template>

    <xsl:template match="@* | node()">
        <xsl:copy>
            <xsl:apply-templates select="@* | node()"/>
        </xsl:copy>
    </xsl:template>


    <xsl:template match="enclosure">
        <media:thumbnail>
            <xsl:attribute name="url">
                <xsl:value-of select="@url"/>
            </xsl:attribute>
        </media:thumbnail>
    </xsl:template>

</xsl:stylesheet>