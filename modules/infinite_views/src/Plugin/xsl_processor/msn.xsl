<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:php="http://php.net/xsl"
                xmlns:dc="http://purl.org/dc/elements/1.1/"
                xmlns:dcterms= "http://purl.org/dc/terms/"
                xmlns:media= "http://search.yahoo.com/mrss/"
                xmlns:atom="http://www.w3.org/2005/Atom"
                xmlns:mi="http://schemas.ingestion.microsoft.com/common/"
                exclude-result-prefixes="php">

    <xsl:output indent="yes" method="xml"/>

    <!-- The title and description are overwritten by the view -->
    <xsl:param name="feed_title" select="'MSN Feed'" />
    <xsl:param name="feed_description" select="'MSN Feed'" />
    <xsl:param name="feed_language" select="'de-de'" />
    <xsl:param name="feed_link" select="'/msn.xml'" />

    <xsl:template match="/">
        <rss version="2.0">
            <channel>
                <title><xsl:value-of select="$feed_title" /></title>
                <language><xsl:value-of select="$feed_language" /></language>
                <link><xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::getFrontendBaseUrl')" /><xsl:value-of select="$feed_link" /></link>
                <description><xsl:value-of select="$feed_description" /></description>
                <atom:link rel="self" type="application/rss+xml">
                  <xsl:attribute name="href">
                    <xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::getFrontendBaseUrl')" /><xsl:value-of select="$feed_link" />
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
            <dcterms:abstract>
              <xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
              <xsl:value-of select="field_teaser_text/value" disable-output-escaping="yes" />
              <xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
            </dcterms:abstract>
            <xsl:apply-templates select="field_teaser_media|field_paragraphs/field_media[bundle/target_id = 'gallery']" />
            <description>
                <xsl:text disable-output-escaping="yes">&lt;![CDATA[</xsl:text>
                <xsl:apply-templates select="field_paragraphs[type/target_id = 'text' or type/target_id = 'quote']|field_paragraphs/field_media[bundle/target_id = 'image']|field_paragraphs/field_media[bundle/target_id = 'instagram']" />
                <xsl:text disable-output-escaping="yes">]]&gt;</xsl:text>
            </description>
            <guid isPermaLink="true"><xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::getCompleteUrl', url)" /></guid>
        </item>
    </xsl:template>



    <!-- paragraph templates -->
    <xsl:template match="field_paragraphs[type/target_id = 'text']">
      <xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::completeURLs', field_text/value)" disable-output-escaping="yes"/>
    </xsl:template>

    <xsl:template match="field_paragraphs[type/target_id = 'quote']">
        <blockquote>
            <p><xsl:value-of select="field_text/value" disable-output-escaping="yes"/></p>
            <footer><xsl:value-of select="field_quote_author/value" disable-output-escaping="yes"/></footer>
        </blockquote>
    </xsl:template>

    <xsl:template match="field_paragraphs/field_media[bundle/target_id = 'image']">
        <figure>
            <img>
              <xsl:attribute name="src"><xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::imageUrl', field_image/uri/value, 'msn_feed')"/></xsl:attribute>
                <xsl:attribute name="title"><xsl:value-of select="field_image/title" /></xsl:attribute>
                <xsl:attribute name="alt"><xsl:value-of select="field_image/alt" /></xsl:attribute>
            </img>
            <figcaption>
                <xsl:value-of select="field_image/title" />
                <span class="copyright"><xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::concat', ', ', field_source/value, field_copyright/value)"/></span>
            </figcaption>
        </figure>
    </xsl:template>

    <xsl:template match="field_paragraphs/field_media[bundle/target_id = 'instagram']">
        <xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::instagram', field_url/uri)" disable-output-escaping="yes"/>
    </xsl:template>

    <xsl:template match="field_paragraphs/field_media[bundle/target_id = 'gallery']">
        <media:group>
            <xsl:apply-templates select="field_media_images" />
        </media:group>
    </xsl:template>

    <xsl:template match="field_paragraphs">
        <xsl:comment>This type of paragraph is left out</xsl:comment>
    </xsl:template>



    <!-- media templates -->

    <xsl:template match="field_teaser_media[bundle/target_id = 'image']|field_images[bundle/target_id = 'image']|field_media_images[bundle/target_id = 'image']">
        <xsl:variable name="syndicationRights" select="field_has_syndication_rights/value" />
        <media:content url="{php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::imageUrl', field_image/uri/value, 'msn_feed')}" type="{field_image/filemime/value}">
            <media:thumbnail url="{php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::imageUrl', thumbnail/uri/value, 'thumbnail')}"/>
            <media:credit><xsl:value-of select="php:functionString('Drupal\xsl_process\DefaultPhpFunctionsProvider::concat', ', ', field_source/value, field_copyright/value)"/></media:credit>
            <media:title><xsl:value-of select="field_description/value" /></media:title>
            <media:text><xsl:value-of select="field_image/alt" /></media:text>
            <mi:hasSyndicationRights>
                <xsl:choose>
                    <xsl:when test="$syndicationRights = 1">1</xsl:when>
                    <xsl:otherwise>0</xsl:otherwise>
                </xsl:choose>
            </mi:hasSyndicationRights>
        </media:content>
    </xsl:template>

</xsl:stylesheet>
