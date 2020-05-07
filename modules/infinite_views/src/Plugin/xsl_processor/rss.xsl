<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:php="http://php.net/xsl"
                xmlns:dc="http://purl.org/dc/elements/1.1/"
                xmlns:dcterms="http://purl.org/dc/terms/"
                xmlns:atom="http://www.w3.org/2005/Atom"
                exclude-result-prefixes="php">

    <xsl:output indent="yes" method="xml"/>

    <!-- The title and description are overwritten by the view -->
    <xsl:param name="feed_title" select="'RSS Feed'" />
    <xsl:param name="feed_description" select="'RSS Feed'" />
    <xsl:param name="feed_language" select="'de-de'" />
    <xsl:param name="feed_link" select="'/rss.xml'" />

    <xsl:template match="/">
        <rss version="2.0">
            <channel>
                <title><xsl:value-of select="$feed_title" /></title>
                <language><xsl:value-of select="$feed_language" /></language>
		<link><xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::getFrontendBaseUrl')" /><xsl:value-of select="$feed_link" /></link>
                <description><xsl:value-of select="$feed_description" /></description>
                <atom:link rel="self" type="application/rss+xml">
                    <xsl:attribute name="href">
		      <link><xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::getFrontendBaseUrl')" /><xsl:value-of select="$feed_link" /></link>
                    </xsl:attribute>
                </atom:link>
                <xsl:apply-templates select="response/item" />
            </channel>
        </rss>
    </xsl:template>

    <xsl:template match="item">
        <item>
            <title><xsl:value-of select="title/value" /></title>
            <link><xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::getCompleteUrl', url)" /></link>
            <dcterms:created><xsl:value-of select="created/value" /></dcterms:created>
            <dcterms:modified><xsl:value-of select="changed/value" /></dcterms:modified>
	    <dc:creator><xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::userDisplayName', uid/target_id)"/></dc:creator>
	    <pubDate><xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::dateRfc', created/value)" /></pubDate>
            <xsl:apply-templates select="field_teaser_media[bundle/target_id = 'image'][1]" />
            <description>
	      <xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
                <xsl:value-of select="field_teaser_text/value" />
	      <xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
            </description>
	    <guid isPermaLink="true"><xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::getCompleteUrl', url)" /></guid>
        </item>
    </xsl:template>

    <!-- media templates -->

    <xsl:template match="field_teaser_media[bundle/target_id = 'image']">
      <enclosure url="{php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::fileUrl', thumbnail/uri/value)}" type="{thumbnail/filemime/value}" length="{thumbnail/filesize/value}"/>
    </xsl:template>

</xsl:stylesheet>
