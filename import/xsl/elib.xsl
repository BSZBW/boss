<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:php="http://php.net/xsl"
    xmlns:xlink="http://www.w3.org/2001/XMLSchema-instance">
    <xsl:output method="xml" indent="yes" encoding="utf-8"/>
    <xsl:param name="institution">elib</xsl:param>
    <xsl:param name="collection">All Sets</xsl:param>
    <xsl:param name="urlPrefix">http</xsl:param>
    <xsl:variable name="inst" />
    <!-- Change Tracking (See: https://vufind.org/wiki/indexing:eprints?s[]=eprints)  -->
    <!--
    <xsl:param name="track_changes">1</xsl:param>
    <xsl:param name="solr_core">biblio</xsl:param>
    -->

    <xsl:template match="oai_dc:dc">
        <add>
            <doc>
                <!-- ID -->
                <!-- Important: This relies on an <identifier> tag being injected by the OAI-PMH harvester. -->
                <field name="id">
                    <xsl:value-of select="//identifier"/>
                </field>

                <!-- Institution -->
                <field name="institution_id">elib</field>

                <!-- Consortium -->
                <field name="consortium">DLR</field>

                <!-- RECORDTYPE -->
                <field name="recordtype">ntrsoai</field>

                <!-- FULLRECORD -->
                <!-- disabled for now; records are so large that they cause memory problems!-->
              <field name="fullrecord">
                  <xsl:copy-of select="php:function('VuFind::xmlAsText', //oai_dc:dc)"/>
              </field>


                <!-- ALLFIELDS -->
                <field name="allfields">
                    <xsl:value-of select="normalize-space(string(//oai_dc:dc))"/>
                </field>

                <!-- INSTITUTION -->
                <!--<field name="institution">
                    <xsl:value-of select="$institution" />
                </field>
                -->

                <!-- COLLECTION -->
                <field name="collection">
                    <xsl:value-of select="$collection" />
                </field>

                <!-- LANGUAGE -->
                <xsl:if test="//dc:language">
                    <xsl:for-each select="//dc:language">
                        <xsl:if test="string-length() > 0">
                            <field name="language">
                                <xsl:value-of select="php:function('VuFind::mapString', normalize-space(string(.)), 'language_map_iso639-1.properties')"/>
                            </field>
                        </xsl:if>
                    </xsl:for-each>
                </xsl:if>

                <!-- FORMAT -->
                <!-- populating the format field with dc.type instead, see TYPE below.
                     if you like, you can uncomment this to add a hard-coded format
                     in addition to the dynamic ones extracted from the record.
                -->
                <!--
                <xsl:if test="//dc:type">
                    <field name="format">
                        <xsl:value-of select="//dc:type" />
                    </field>
                </xsl:if>
                -->

                <xsl:if test="//dc:type">
                    <xsl:for-each select="//dc:type">
                        <xsl:if test="string-length() > 0">
                            <xsl:if test="position()=1">
                                <field name="format">
                                    <xsl:value-of select="php:function('VuFind::mapString', normalize-space(string(.)), 'format_map.properties')"/>
                                </field>
                            </xsl:if>
                        </xsl:if>
                    </xsl:for-each>
                </xsl:if>


                <!-- Institution -->
                <!--
                <xsl:if test="//dc:subject">
                    <xsl:for-each select="//dc:subject">
                        <xsl:if test="string-length() > 0">
                            <field name="department">
                                <xsl:value-of select="normalize-space()"/>
                            </field>
                        </xsl:if>
                    </xsl:for-each>
                </xsl:if>
                -->

                <xsl:if test="//SetName">
                    <xsl:for-each select="//SetName">
                        <xsl:if test="string-length() > 0">
                            <!-- Instutution names?! -->
                            <!-- Substring -->
                            <!-- starting allways with "Institute und Einrichtungen = " -->
                            <!-- stopping before the first colon ':' -->
                            <!-- skip all empty strings -->
                            <xsl:variable name="inst">
                                <xsl:choose>
                                    <xsl:when test="contains(., ':')">
                                        <xsl:value-of select="substring-before(substring-after(.,'Institute und Einrichtungen = '),': ')"/>
                                    </xsl:when>
                                    <xsl:otherwise>
                                        <xsl:value-of select="substring-after(.,'Institute und Einrichtungen = ')"/>
                                    </xsl:otherwise>
                                </xsl:choose>
                            </xsl:variable>
                            <xsl:if test="$inst != ''">
                                <field name="institute">
                                    <xsl:value-of select="$inst"/>
                                </field>
                            </xsl:if>
                        </xsl:if>
                    </xsl:for-each>
                </xsl:if>

                <!-- DESCRIPTION -->
                <xsl:if test="//dc:description">
                    <field name="description">
                        <xsl:value-of select="//dc:description" />
                    </field>
                </xsl:if>

                <!-- ADVISOR / CONTRIBUTOR -->
                <xsl:if test="//dc:contributor[normalize-space()]">
                    <field name="author_additional">
                        <xsl:value-of select="//dc:contributor[normalize-space()]" />
                    </field>
                </xsl:if>

                <!-- TYPE (Conference / Journal title) -->
                <!--
                <xsl:if test="//dc:type">
                    <field name="container_title">
                        <xsl:value-of select="//dc:type" />
                    </field>
                </xsl:if>
                -->

                <!-- AUTHOR -->
                <xsl:if test="//dc:creator">
                    <xsl:for-each select="//dc:creator">
                        <xsl:if test="normalize-space()">
                            <!-- author is not a multi-valued field, so we'll put
                                first value there and subsequent values in author2.
                            -->
                            <xsl:if test="position()=1">
                                <field name="author">
                                    <xsl:value-of select="normalize-space()"/>
                                </field>
                                <!--
                                <field name="author-letter">
                                    <xsl:value-of select="normalize-space()"/>
                                </field>
                                -->
                            </xsl:if>
                            <xsl:if test="position()>1">
                                <field name="author2">
                                    <xsl:value-of select="normalize-space()"/>
                                </field>
                            </xsl:if>
                        </xsl:if>
                    </xsl:for-each>
                </xsl:if>

                <!-- TITLE -->
                <xsl:if test="//dc:title[normalize-space()]">
                    <field name="title">
                        <xsl:value-of select="//dc:title[normalize-space()]"/>
                    </field>
                    <field name="title_short">
                        <xsl:value-of select="//dc:title[normalize-space()]"/>
                    </field>
                    <field name="title_full">
                        <xsl:value-of select="//dc:title[normalize-space()]"/>
                    </field>
                    <field name="title_sort">
                        <xsl:value-of select="php:function('VuFind::stripArticles', string(//dc:title[normalize-space()]))"/>
                    </field>
                </xsl:if>

                <!-- PUBLISHER -->
                <xsl:if test="//dc:publisher[normalize-space()]">
                    <field name="publisher">
                        <xsl:value-of select="//dc:publisher[normalize-space()]"/>
                    </field>
                </xsl:if>

                <!-- PUBLISHDATE -->
                <xsl:if test="//dc:date">
                    <field name="publishDate">
                        <xsl:value-of select="substring(//dc:date, 1, 4)"/>
                    </field>
                    <field name="publishDateSort">
                        <xsl:value-of select="substring(//dc:date, 1, 4)"/>
                    </field>
                </xsl:if>

                <!-- Change Tracking (See: https://vufind.org/wiki/indexing:eprints?s[]=eprints)  -->
                <!--
                <xsl:if test="$track_changes != 0">
                    <field name="first_indexed">
                        <xsl:value-of select="php:function('VuFind::getFirstIndexed', $solr_core, string(//identifier), string(//datestamp))"/>
                    </field>
                    <field name="last_indexed">
                        <xsl:value-of select="php:function('VuFind::getLastIndexed', $solr_core, string(//identifier), string(//datestamp))"/>
                    </field>
                </xsl:if>
                -->

                <!-- URL -->
                <xsl:for-each select="//dc:relation">
                    <xsl:if test="substring(., 1, string-length($urlPrefix)) = $urlPrefix">
                        <field name="url">
                            <xsl:value-of select="." />
                        </field>
                    </xsl:if>
                </xsl:for-each>
            </doc>
        </add>
    </xsl:template>
</xsl:stylesheet>
