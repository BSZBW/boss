<?php
/**
 * Recorddriver for Solr records from the aggregated index of Leipzig University
 * Library
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2015.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace Finc\RecordDriver;

use Exception;
use VuFind\RecordDriver\Response\PublicationDetails;
use VuFindHttp\HttpServiceAwareInterface as HttpServiceAwareInterface;
use VuFindHttp\HttpServiceAwareTrait;

/**
 * Recorddriver for Solr records from the aggregated index of Leipzig University
 * Library
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class SolrAI extends SolrDefault implements
    HttpServiceAwareInterface
{
    use HttpServiceAwareTrait;

    /**
     * AI record
     *
     * @var array
     */
    protected $aiRecord;

    /**
     * Holds config.ini data
     *
     * @var array
     */
    protected $mainConfig;

    /**
     * Returns true if the record supports real-time AJAX status lookups.
     *
     * @return bool
     */
    public function supportsAjaxStatus()
    {
        return true;
    }

    /**
     * Gets the description of the record
     *
     * @return string description
     */
    public function getDescriptions()
    {
        return $this->getAIRecord('abstract');
    }

    /**
     * Gets the edition key from the record
     *
     * @return string edition
     */
    public function getEdition()
    {
        return $this->getAIRecord('rft.edition');
    }

    /**
     * Gets the doi of the record
     *
     * @return string publication date
     */
    public function getDOI()
    {
        return $this->getAIRecord('doi');
    }

    /**
     * Gets an array of issues from record
     *
     * @return array of issues
     */
    public function getIssues()
    {
        return $this->getAIRecord('rft.issue');
    }

    /**
     * Has FirstPublicationsDetails a Date in it
     *
     * @return boolean
     */
    protected function getIsPublicationDetailsDate()
    {
        return true;
    }

    /**
     * Get the main author of the record.
     *
     * @return string
     */
    /*public function getPrimaryAuthor()
    {
        return null;
    }*/

    /**
     * Get additional entries for personal names.
     *
     * @return array
     * @link http://www.loc.gov/marc/bibliographic/bd700.html
     */
    protected function getAdditionalAuthors()
    {
        $authors = $this->getAIRecord('authors');
        if (!empty($authors)
            && is_array($authors)
            && (count($authors) > 0)
        ) {
            $retval = [];
            $i = 0;
            foreach ($authors as $value) {
                $author = false;
                if (isset($value['rft.aulast']) || isset($value['rft.aufirst'])) {
                    $author
                        = (isset($value['rft.aulast'])
                            ? $value['rft.aulast'] . ', '
                            : '') .
                        ($value['rft.aufirst'] ?? '');
                } else {
                    $author = ($value['rft.au'] ?? '');
                }
                $retval[$i]['name'] = $author;
                $i++;
            }
            return $retval;
        }
        return [];
    }

    /**
     * Get the title of the item that contains this record (i.e. MARC 773s of a
     * journal).
     *
     * @return string
     */
    public function getContainerTitle()
    {
        return isset($this->fields['container_title']) ?
                $this->fields['container_title'] : '';
    }

    /**
     * Get an array of publication detail lines combining information from
     * getPublicationDates(), getPublishers() and getPlacesOfPublication().
     *
     * @return array
     */
    public function getPublicationDetails()
    {
        $names =  $this->getAIRecord('rft.pub');
        $i = 0;
        $retval = [];
        while (!empty($names[$i])) {
            // Build objects to represent each set of data; these will
            // transform seamlessly into strings in the view layer.
            $retval[] = new PublicationDetails(
                null,
                $names[$i] ?? '',
                null
            );
            $i++;
        }
        return $retval;
    }

    /**
     * Returns an array with the necessary information to create a detailed
     * "Published in" line in RecordDriver core.phtml
     *
     * @return array
     * @todo revision of method due
     */
    public function getPublishedIn()
    {
        return [
            'jtitle' => $this->getJTitle(),
            'volume' => $this->getVolume(),
            'date'   => $this->getPublishDateSort(),
            'issue'  => $this->getIssues(),
            'issns'  => $this->getISSNs(),
            'pages'  => $this->getPages()
        ];
    }

    /**
     * Gets an array of sources (mega_collection) from record
     *
     * @return array of sources
     */
    public function getMegaCollection()
    {
        return $this->getAIRecord('finc.mega_collection');
    }

    /**
     * Gets an array of series from record
     *
     * @return array of series
     */
    public function getSeries()
    {
        return $this->getAIRecord('rft.series');
    }

    /**
     * Gets an array of volumes from record
     *
     * @return array of volumes
     */
    public function getVolume()
    {
        return $this->getAIRecord('rft.volume');
    }

    /**
     * Get an array of all ISSNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISSNs() : array
    {
        return isset($this->fields['issn']) ? $this->fields['issn'] : [];
    }

    /**
     * Get the eISSN from a record.
     *
     * @return array
     */
    public function getEISSNs()
    {
        return $this->getAIRecord('rft.eissn');
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISBNs() : array
    {
        return isset($this->fields['isbn']) ? $this->fields['isbn'] : [];
    }

    /**
     * Get pages as 'start-end' if both exist
     *
     * @return string pages
     */
    public function getPages()
    {
        // startpage
        $spage = $this->getAIRecord('rft.spage');
        // endpage
        $epage = $this->getAIRecord('rft.epage');
        // pages
        $pages = $this->getAIRecord('rft.pages');
        if (!empty($spage) && !empty($epage)) {
            return sprintf('%s-%s', $spage, $epage);
        } elseif (!empty($spage)) {
            return $spage[0];
        } elseif (!empty($epage)) {
            return $epage[0];
        } elseif (!empty($pages)) {
            return $pages;
        }

        return '';
    }

    /**
     * Return the rft.jtitle field of ai records
     *
     * @return array   Return jtitle fields.
     */
    public function getJTitle()
    {
        return $this->getAIRecord('rft.jtitle');
    }

    /**
     * Return the rft.atitle field of ai records
     *
     * @return array   Return atitle fields.
     */
    public function getATitle()
    {
        return $this->getAIRecord('rft.atitle');
    }

    /**
     * Return the rft.btitle field of ai records
     *
     * @return array   Return btitle fields.
     */
    public function getBTitle()
    {
        return $this->getAIRecord('rft.btitle');
    }

    /**
     * Get the OpenURL parameters to represent this record (useful for the
     * title attribute of a COinS span tag).
     *
     * @param bool $overrideSupportsOpenUrl Flag to override checking
     * supportsOpenUrl() (default is false)
     *
     * @return string OpenURL parameters.
     */
    public function getOpenUrl($overrideSupportsOpenUrl = false)
    {
        // stop here if this record does not support OpenURLs
        if (!$overrideSupportsOpenUrl && !$this->supportsOpenUrl()) {
            return false;
        }

        $genre = $this->getAIRecord('rft.genre');
        // Set up parameters based on the format of the record:
        switch ($genre) {
        case "article":
            $params = $this->getArticleOpenURLParams();
            break;
        case "book":
        case "bookitem":
            $params = $this->getBookOpenURLParams();
            break;
        case "journal":
            $params = $this->getJournalOpenURLParams();
            break;
        case "conference":
        case "document":
        case "issue":
        case "preprint":
        case "proceeding":
        case "report":
        case "unknown":
        default:
            $format = $this->getFormats();
            $params = $this->getUnknownFormatOpenURLParams($format);
            break;
        }

        // Assemble the URL:
        return http_build_query($params);
    }

    /**
     * Get the COinS identifier.
     *
     * @return string
     */
    protected function getCoinsID()
    {
        // Get the COinS ID -- it should be in the OpenURL section of config.ini,
        // but we'll also check the COinS section for compatibility with legacy
        // configurations (this moved between the RC2 and 1.0 releases).
        if (isset($this->mainConfig->OpenURL->rfr_id)
            && !empty($this->mainConfig->OpenURL->rfr_id)
        ) {
            return $this->mainConfig->OpenURL->rfr_id;
        }
        return 'vufind.svn.sourceforge.net';
    }

    /**
     * Get OpenURL parameters for an article.
     *
     * @return array
     */
    protected function getArticleOpenURLParams()
    {
        $params = $this->getDefaultOpenURLParams();
        // unset default title -- we only want jtitle/atitle here:
        //$params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
        $params['genre'] = 'article';
        $value = $this->getAIRecord('finc.record_id');
        if (!empty($value)) {
            $params['rft_id'] = $value;
        }
        $value = $this->getAIRecord('rft.issn');
        if (!empty($value)) {
            foreach ($value as $issn) {
                $params['issn'] = $issn;
            }
        }
        // an article may have also an ISBN:
        $value = $this->getAIRecord('rft.isbn');
        if (!empty($value)) {
            $params['isbn'] = $value;
        }
        $value = $this->getAIRecord('rft.ssn');
        if (!empty($value)) {
            $params['ssn'] = $value;
        }
        $value = $this->getAIRecord('rft.volume');
        if (!empty($value)) {
            $params['volume'] = $value;
        }
        $value = $this->getAIRecord('rft.issue');
        if (!empty($value)) {
            $params['issue'] = $value;
        }
        $value = $this->getAIRecord('rft.spage');
        if (!empty($value)) {
            $params['spage'] = $value;
        }
        $value = $this->getAIRecord('rft.epage');
        if (!empty($value)) {
            $params['epage'] = $value;
        }
        $value = $this->getAIRecord('rft.pages');
        if (!empty($value)) {
            $params['pages'] = $value;
        }
        $value = $this->getAIRecord('rft.coden');
        if (!empty($value)) {
            $params['coden'] = $value;
        }
        $value = $this->getAIRecord('rft.artnum');
        if (!empty($value)) {
            $params['artnum'] = $value;
        }
        $value = $this->getAIRecord('rft.sici');
        if (!empty($value)) {
            $params['sici'] = $value;
        }
        $value = $this->getAIRecord('rft.chron');
        if (!empty($value)) {
            $params['chron'] = $value;
        }
        $value = $this->getAIRecord('rft.quarter');
        if (!empty($value)) {
            $params['quarter'] = $value;
        }
        $value = $this->getAIRecord('rft.part');
        if (!empty($value)) {
            $params['part'] = $value;
        }
        $value = $this->getAIRecord('rft.jtitle');
        if (!empty($value)) {
            $params['jtitle'] = $value;
        }
        $value = $this->getAIRecord('rft.atitle');
        if (!empty($value)) {
            $params['atitle'] = $value;
        }
        $value = $this->getAIRecord('rft.stitle');
        if (!empty($value)) {
            $params['stitle'] = $value;
        }
        $value = $this->getAIRecord('authors');
        if (!empty($value)) {
            foreach ($value as $author) {
                if (isset($author['rft.au'])) {
                    $params['au'] = $author['rft.au'];
                }
                if (isset($author['rft.aulast'])) {
                    $params['aulast'] = $author['rft.aulast'];
                }
                if (isset($author['rft.aucorp'])) {
                    $params['aucorp'] = $author['rft.aucorp'];
                }
                if (isset($author['rft.auinitm'])) {
                    $params['auinitm'] = $author['rft.auinitm'];
                }
                if (isset($author['rft.aufirst'])) {
                    $params['aufirst'] = $author['rft.aufirst'];
                }
                if (isset($author['rft.auinit'])) {
                    $params['auinit'] = $author['rft.auinit'];
                }
                if (isset($author['rft.auinit1'])) {
                    $params['auinit1'] = $author['rft.auinit1'];
                }
                if (isset($author['rft.ausuffix'])) {
                    $params['ausuffix'] = $author['rft.ausuffix'];
                }
            }
        }
        $value = $this->getAIRecord('rft.format');
        if (!empty($value)) {
            $params['format'] = $value;
        }
        $value = $this->getAIRecord('doi');
        if (!empty($value)) {
            $params['rft_id'] = 'info:doi/' . $value;
        }
        $value = $this->getAIRecord('languages');
        if (!empty($value)) {
            $params['rft.language'] = $value;
        }
        $value = $this->getAIRecord('rft.date');
        if (!empty($value)) {
            $params['rft.date'] = $value;
        }
        return $params;
    }

    /**
     * Get OpenURL parameters for a book.
     *
     * @return array
     */
    protected function getBookOpenURLParams()
    {
        $params = $this->getDefaultOpenURLParams();
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
        $params['genre'] = 'book';
        $value = $this->getAIRecord('rft.atitle');
        if (!empty($value)) {
            $params['atitle'] = $this->getATitle();
        }
        $value = $this->getAIRecord('rft.btitle');
        if (!empty($value)) {
            $params['rft.btitle'] = $this->getBTitle();
        }
        $value = $this->getAIRecord('finc.record_id');
        if (!empty($value)) {
            $params['rft_id'] = $value;
        }
        $value = $this->getAIRecord('rft.issn');
        if (!empty($value)) {
            foreach ($value as $issn) {
                $params['issn'] = $issn;
            }
        }
        $value = $this->getAIRecord('rft.edition');
        if (!empty($value)) {
            $params['edition'] = $value;
        }
        $value = $this->getAIRecord('rft.isbn');
        if (!empty($value)) {
            $params['isbn'] = $value;
        }
        $value = $this->getAIRecord('rft.ssn');
        if (!empty($value)) {
            $params['ssn'] = $value;
        }
        $value = $this->getAIRecord('rft.eissn');
        if (!empty($value)) {
            $params['eissn'] = $value;
        }
        $value = $this->getAIRecord('rft.volume');
        if (!empty($value)) {
            $params['volume'] = $value;
        }
        $value = $this->getAIRecord('rft.issue');
        if (!empty($value)) {
            $params['issue'] = $value;
        }
        $value = $this->getAIRecord('rft.spage');
        if (!empty($value)) {
            $params['spage'] = $value;
        }
        $value = $this->getAIRecord('rft.epage');
        if (!empty($value)) {
            $params['epage'] = $value;
        }
        $value = $this->getAIRecord('rft.pages');
        if (!empty($value)) {
            $params['pages'] = $value;
        }
        $value = $this->getAIRecord('rft.series');
        if (!empty($value)) {
            $params['series'] = $value;
        }
        $value = $this->getAIRecord('rft.tpages');
        if ($value) {
            $params['tpages'] = $this->aiRecord['rft.tpages'];
        }
        $value = $this->getAIRecord('rft.bici');
        if (!empty($value)) {
            $params['bici'] = $value;
        }
        $value = $this->getAIRecord('authors');
        if (!empty($value)) {
            foreach ($value as $author) {
                if (isset($author['rft.au'])) {
                    $params['au'] = $author['rft.au'];
                }
                if (isset($author['rft.aulast'])) {
                    $params['aulast'] = $author['rft.aulast'];
                }
                if (isset($author['rft.aucorp'])) {
                    $params['aucorp'] = $author['rft.aucorp'];
                }
                if (isset($author['rft.auinitm'])) {
                    $params['auinitm'] = $author['rft.auinitm'];
                }
                if (isset($author['rft.aufirst'])) {
                    $params['aufirst'] = $author['rft.aufirst'];
                }
                if (isset($author['rft.auinit'])) {
                    $params['auinit'] = $author['rft.auinit'];
                }
                if (isset($author['rft.auinit1'])) {
                    $params['auinit1'] = $author['rft.auinit1'];
                }
                if (isset($author['rft.ausuffix'])) {
                    $params['ausuffix'] = $author['rft.ausuffix'];
                }
            }
        }
        $value = $this->getAIRecord('rft.format');
        if (!empty($value)) {
            $params['format'] = $value;
        }
        $value = $this->getAIRecord('doi');
        if (!empty($value)) {
            $params['rft_id'] = 'info:doi/' . $value;
        }
        $publishers = $this->getPublishers();
        if (count($publishers) > 0) {
            $params['rft.pub'] = $publishers[0];
        }
        return $params;
    }

    /**
     * Retrieve data from ai-blobserver
     *
     * @param string $id      Record Id of the raw recorddata to be retrieved
     * @param string $baseUrl The Ai fullrecord server url.
     *
     * @return mixed          Raw curl request response (should be json).
     * @throws Exception
     */
    protected function retrieveAiFullrecord($id, $baseUrl)
    {
        if (!isset($id)) {
            throw new Exception('no id given');
        }

        $url = sprintf($baseUrl, $id);

        try {
            $response = $this->httpService->get($url);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        if (!$response->isSuccess()) {
            $this->debug(
                'HTTP status ' . $response->getStatusCode() .
                ' received, retrieving data for record: ' . $id
            );

            return false;
        }

        return $response->getBody();
    }

    /**
     * Returns the AI fullrecord as decoded json.
     *
     * @param string $id Record id to be retrieved.
     *
     * @return array
     * @throws Exception
     */
    protected function getAIJSONFullrecord($id)
    {
        if (!isset($this->recordConfig->General)) {
            throw new Exception('SolrAI General settings missing.');
        }

        $baseUrl = $this->recordConfig->General->baseUrl;

        if (!isset($baseUrl)) {
            throw new Exception('no ai-blobserver configurated');
        }

        $response = $this->retrieveAiFullrecord($id, $baseUrl);

        return json_decode($response, true);
    }

    /**
     * Returns the value of a certain record key or the default value if not exists
     *
     * @param string $key Key of record array
     *
     * @return mixed value of key
     */
    public function getAIRecord($key = null)
    {
        $id = $this->getID();
        if (empty($this->aiRecord) && !empty($id)) {
            $this->aiRecord = $this->getAIJSONFullrecord($id);
        }
        if (null !== $key) {
            if (!isset($this->aiRecord[$key])
                && !empty($this->aiRecord[$key])
                && !is_array($this->aiRecord[$key])
                && (count($this->aiRecord[$key]) == 0)
            ) {
                return '';
            } elseif (empty($this->aiRecord[$key])) {
                return [];
            }
            return $this->aiRecord[$key];
        }
        return $this->aiRecord;
    }

    /**
     * Gets an array of publishers from the AI-blob
     *
     * @return array of publishers
     */
    public function getPublishersFromRawData()
    {
        return $this->getAIRecord('rft.pub');
    }

    /**
     * Gets id of ai record
     *
     * @return string id
     */
    public function getID()
    {
        return isset($this->fields['id']) ? $this->fields['id'] : '';
    }

    /**
     * Get an array of strings representing citation formats supported
     * by this record's data (empty if none).  For possible legal values,
     * see /application/themes/root/helpers/Citation.php.
     *
     * @return array Strings representing citation formats.
     */
    protected function getSupportedCitationFormats()
    {
        return ['APAAI', 'MLAAI'];
    }

    /**
     * Check for article ode e-article format
     *
     * @return mixed
     */
    public function isArticle()
    {
        if (isset($this->aiRecord['rft.genre']) && $this->aiRecord['rft.genre'] === "article") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getContainerPages()
    {
        return $this->getAIRecord('rft.pages');
    }

    /**
     * @return mixed|string
     */
    public function getContainerYear()
    {
        return $this->getPublishDateSort();
    }

    /**
     * Strip HTML tags from description field
     *
     * @return array|mixed
     */
    public function getSummary()
    {
        $summary = [];
        if (isset($this->fields['description'])
            && !empty($this->fields['description'])
        ) {
            $summary = $this->fields['description'];
            if (is_array($summary)) {
                foreach ($summary as $k => $sum) {
                    $summary[$k] = strip_tags($sum);
                }
            } else {
                $summary = [strip_tags($summary)];
            }
        }
        return $summary;
    }

    /**
     * Adjust FincAI formats so they better fit to our solr formats
     *
     * @return array
     */
    public function getFormats() : array
    {
        $parent = parent::getFormats();
        foreach ($parent as $k => $p) {
            $regex = '/Electronic|E-/i';
            if (preg_match($regex, $p)) {
                $p = preg_replace($regex, '', $p);
                $parent[$k] = $p;
                $parent[] = 'Online';
            }
        }
        if (in_array('Online', $this->fields['facet_avail'])) {
            $parent[] = 'Online';
        }
        return array_unique($parent);
    }

}
