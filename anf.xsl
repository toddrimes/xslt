<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:media="http://search.yahoo.com/mrss/"
                xmlns:content="http://purl.org/rss/1.0/modules/content/"
                xmlns:dc="http://purl.org/dc/elements/1.1/"
                xmlns:saxon="http://saxon.sf.net/"
                xmlns:xhtml="http://www.w3.org/1999/xhtml">

<!--     exclude-result-prefixes="content saxon xhtml" -->
    <xsl:output omit-xml-declaration="yes" indent="yes"
                cdata-section-elements="description content:encoded content encoded"/>
    <xsl:param name="quote" select="'&quot;'"/>

    <xsl:template name="doublequotes">
        <xsl:param name="text" select="."/>
        <xsl:variable name="quot">"</xsl:variable>
        <xsl:choose>
            <xsl:when test="contains($text, $quot)">
                <xsl:value-of select="substring-before($text, $quot)" disable-output-escaping="yes"/>
                <xsl:text>\"</xsl:text>
                <xsl:call-template name="doublequotes">
                    <xsl:with-param name="text" select="substring-after($text, $quot)"/>
                </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
                <xsl:value-of select="$text" disable-output-escaping="yes"/>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>

    <xsl:template name="rss" match="/">
        <rss xmlns:content="http://purl.org/rss/1.0/modules/content/"
           xmlns:dc="http://purl.org/dc/elements/1.1/"
                    xmlns:media="http://search.yahoo.com/mrss/">
            <xsl:for-each select="rss">
                <xsl:attribute name="version">
                    <xsl:value-of select="string(number(string(@version)))"/>
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

    <xsl:template match="item">
        <item>
            <componentLayouts>
                <authorLayout>
                    <columnSpan>7</columnSpan>
                    <columnStart>0</columnStart>
                    <margin>
                        <bottom>15</bottom>
                        <top>15</top>
                    </margin>
                </authorLayout>
                <bodyLayout>
                    <columnSpan>5</columnSpan>
                    <columnStart>0</columnStart>
                    <margin>
                        <bottom>15</bottom>
                        <top>15</top>
                    </margin>
                </bodyLayout>
                <headerImageLayout>
                    <columnSpan>7</columnSpan>
                    <columnStart>0</columnStart>
                    <ignoreDocumentMargin>true</ignoreDocumentMargin>
                    <margin>
                        <bottom>15</bottom>
                        <top>15</top>
                    </margin>
                    <minimumHeight>40vh</minimumHeight>
                </headerImageLayout>
                <introLayout>
                    <columnSpan>7</columnSpan>
                    <columnStart>0</columnStart>
                    <margin>
                        <bottom>15</bottom>
                        <top>15</top>
                    </margin>
                </introLayout>
                <titleLayout>
                    <columnSpan>7</columnSpan>
                    <columnStart>0</columnStart>
                    <margin>
                        <bottom>10</bottom>
                        <top>50</top>
                    </margin>
                </titleLayout>
            </componentLayouts>
            <componentTextStyles>
                <authorStyle>
                    <fontName>HelveticaNeue-Bold</fontName>
                    <fontSize>16</fontSize>
                    <textAlignment>left</textAlignment>
                    <textColor>#000</textColor>
                </authorStyle>
                <bodyStyle>
                    <fontName>Georgia</fontName>
                    <fontSize>18</fontSize>
                    <lineHeight>26</lineHeight>
                    <textAlignment>left</textAlignment>
                    <textColor>#000</textColor>
                </bodyStyle>
                <default-subtitle>
                    <fontName>HelveticaNeue-Thin</fontName>
                    <fontSize>20</fontSize>
                    <lineHeight>24</lineHeight>
                    <textAlignment>center</textAlignment>
                    <textColor>#2F2F2F</textColor>
                </default-subtitle>
                <default-title>
                    <fontName>HelveticaNeue-Thin</fontName>
                    <fontSize>36</fontSize>
                    <lineHeight>44</lineHeight>
                    <textAlignment>center</textAlignment>
                    <textColor>#2F2F2F</textColor>
                </default-title>
                <introStyle>
                    <fontName>HelveticaNeue-Medium</fontName>
                    <fontSize>24</fontSize>
                    <textAlignment>left</textAlignment>
                    <textColor>#000</textColor>
                </introStyle>
                <titleStyle>
                    <fontName>HelveticaNeue-Bold</fontName>
                    <fontSize>64</fontSize>
                    <lineHeight>74</lineHeight>
                    <textAlignment>left</textAlignment>
                    <textColor>#000</textColor>
                </titleStyle>
            </componentTextStyles>
            <components>
                <element>
                    <layout>titleLayout</layout>
                    <role>title</role>
                    <text><xsl:value-of select="title" disable-output-escaping="yes"/></text>
                    <textStyle>titleStyle</textStyle>
                </element>
                <element>
                    <layout>introLayout</layout>
                    <role>intro</role>
                    <text><xsl:value-of
                            select="normalize-space(translate(description,'&#xA;',''))"
                            disable-output-escaping="yes"/></text>
                    <textStyle>introStyle</textStyle>
                </element>
                <element>
                    <layout>headerImageLayout</layout>
                    <role>header</role>
                    <style>
                        <fill>
                            <URL><xsl:value-of select="enclosure/@url"/></URL>
                            <fillMode>cover</fillMode>
                            <type>image</type>
                            <verticalAlignment>center</verticalAlignment>
                        </fill>
                    </style>
                </element>
                <element>
                    <layout>authorLayout</layout>
                    <role>author</role>
                    <text><xsl:value-of select="dc:creator" disable-output-escaping="yes"/></text>
                    <textStyle>authorStyle</textStyle>
                </element>
                <xsl:apply-templates select="content:encoded"/>
            </components>
            <documentStyle>
                <backgroundColor>#f6f6f6</backgroundColor>
            </documentStyle>
            <identifier><xsl:value-of select="guid" disable-output-escaping="yes"/></identifier>
            <language>en</language>
            <layout>
                <columns>7</columns>
                <gutter>40</gutter>
                <margin>70</margin>
                <width>1024</width>
            </layout>
            <metadata>
                <excerpt><xsl:value-of
                        select="normalize-space(translate(description,'&#xA;',''))"
                        disable-output-escaping="yes"/></excerpt>
                <thumbnailURL><xsl:value-of select="enclosure/@url"/></thumbnailURL>
            </metadata>
            <subtitle>Get more at <a href="http://www.thespruce.com">The Spruce</a>.</subtitle>
            <title><xsl:value-of
                    select="normalize-space(translate(title,'&#xA;',''))"
                    disable-output-escaping="yes"/></title>
            <version>1.0</version>
        </item>
    </xsl:template>

    <xsl:template match="description">
        <text>"<xsl:call-template name="doublequotes"/>"</text>
    </xsl:template>

    <xsl:template match="content:encoded">
        <element role="body">
            <html>
                <xsl:value-of select="normalize-space(translate(.,'&#xA;',''))"
                              disable-output-escaping="no"/>
            </html>
        </element>
    </xsl:template>

</xsl:stylesheet>