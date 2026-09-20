<?php
/**
 * finc specific model for Solr records based on the stock
 * VuFind\RecordDriver\SolrDefault
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
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
namespace Finc\RecordDriver;

use VuFindSearch\ParamBag;
use VuFindSearch\Query\Query as Query;

/**
 * finc specific model for Solr records based on the stock
 * VuFind\RecordDriver\SolrDefault
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
trait SolrDefaultFincTrait
{
    /**
     * Customized isCollection() to add a check if the record is a single element
     * collection.
     *
     * @return bool
     */
    public function isCollection()
    {
        return $this->getRecordType() === 'dico';
        /*
        // first check as always if we have a collection
        $isCollection = parent::isCollection();

        if ($isCollection) {
            // if we have a collection only return true if
            // isSingleElementHierarchyRecord is false
            return !$this->isSingleElementHierarchyRecord();
        }

        // if we've come so far this record is no collection
        return false;
        */
    }

    /**
     * Get all call numbers associated with the record (empty string if none).
     *
     * @return array
     */
    public function getCallNumbers()
    {
        return isset($this->fields['callnumber_' . $this->indexExtension])
            ? $this->fields['callnumber_' . $this->indexExtension]
            : parent::getCallNumbers();
    }

    /**
     * Return the custom index field local_heading if indexExtension is set.
     * If indexExtension is set local_heading_{indexExtension} is returned,
     * if local_heading_{indexExtesion} is empty,
     * local_heading_facet_{indexExtension} is returned.
     *
     * @return array   Containing local_heading_[facet_]{indexExtension} fields.
     */
    public function getLocalHeading()
    {
        $array = [];

        if (isset($this->mainConfig->CustomIndex->indexExtension)) {
            $array = isset($this->fields['local_heading_' . ($this->mainConfig->CustomIndex->indexExtension)]) ?
                $this->fields['local_heading_' . ($this->mainConfig->CustomIndex->indexExtension)] : [];
            // Use local_heading_facet field if local_heading field delivers no results at first
            if (count($array) == 0) {
                $array = isset($this->fields['local_heading_facet_' . ($this->mainConfig->CustomIndex->indexExtension)]) ?
                    $this->fields['local_heading_facet_' . ($this->mainConfig->CustomIndex->indexExtension)] : [];
            }
        }
        return $array;
    }

    /**
     * Custom method to return permissions set for this record in mainConfig
     *
     * @return int|null|string
     */
    public function getRecordPermission()
    {
        // do we have a RecordPermissions section in config.ini?
        if (isset($this->mainConfig->RecordPermissions)) {

            // let's loop through all set RecordPermissions and evaluate them for the
            // current record
            foreach ($this->mainConfig->RecordPermissions->toArray() as $permission => $settings) {
                foreach ((array)$settings as $value) {
                    list($methodName, $methodReturn) = explode(':', $value);
                    if (in_array($methodReturn, (array)$this->tryMethod($methodName))) {
                        // as the current permission matches the current record,
                        // return it
                        return $permission;
                    }
                }
            }
        }
        // either no permissions were set or none matched, so return null
        return null;
    }

    /**
     * Controller to decide when local format field of a library should be
     * retrieved from marc. Pass through method for PrimoCentral
     *
     * Public method for tuf to display format at search modul.
     *
     * @internal        This method should be dropped or renamed (getStandardFormat())
     *                  as it is only a wrapper for the custom method getFormat() which
     *                  in turn behaves as the stock getFormats() method.
     *
     * @deprecated      No need for this wrapper in custom SolrDefault
     *
     * @return array
     */
    public function getLocalFormat()
    {
        return $this->getFormat();
    }

    /**
     * Get the original edition of the record.
     *
     * @return string
     */
    public function getEditionOrig()
    {
        // Not indexed in Solr yet.
        return null;
    }

    /**
     * Get an array of all footnotes in the record.
     *
     * @return array
     */
    public function getFootnotes()
    {
        return isset($this->fields['footnote']) ? $this->fields['footnote'] : [];
    }

    /**
     * Get an array of dissertation notes.
     *
     * @return null
     */
    public function getDissertationNote()
    {
        return null;
    }

    /**
     * Get back the standardizied format field of Solr index.
     *
     * @deprecated      Should also be possible to be dropped (@see getLocalFormat())
     *
     * @return array
     */
    public function getFormat()
    {
        return isset($this->fields['format']) ? $this->fields['format'] : [];
    }

    /**
     * Get an array of all the formats associated with the record. If indexExtension
     * is set and generalFormats is disabled in config.ini return the field
     * format_{indexExtension}, format otherwise.
     *
     * @return array        Array with formats associated with the record.
     */
    public function getFormats()
    {
        // check if general 'format' index field should be used
        $isGeneralFormat = (isset($this->mainConfig->CustomIndex->generalFormats)
            && true == $this->mainConfig->CustomIndex->generalFormats)
            ? true : false;
        // check if there's an extension defined for the library depended format
        // index field
        $isExtension = (isset($this->mainConfig->CustomIndex->indexExtension))
            ? true : false;

        $format = (false === $isGeneralFormat && true === $isExtension)
            ? 'format_' . $this->mainConfig->CustomIndex->indexExtension : 'format';

        return isset($this->fields[$format]) ? $this->fields[$format] : [];
    }

    /**
     * Support method for getOpenUrl() -- pick the OpenURL format.
     *
     * @return string
     */
    protected function getOpenUrlFormat()
    {
        $explode_formats = function ($formats) {
            $exploded_formats = [];
            foreach ($formats as $format) {
                $exploded_formats = array_merge(
                    $exploded_formats,
                    array_map("trim", explode(",", $format))
                );
            }
            return count($exploded_formats) ? $exploded_formats : $formats;
        };

        // If we have multiple formats, Book, Journal and Article are most
        // important...
        $formats = $this->getFormats();

        $formats = $explode_formats($formats);

        if (strlen($this->getCleanISSN()) > 0) {
            return 'Journal';
        } elseif (strlen($this->getCleanISBN()) > 0) {
            return 'Book';
        } elseif (in_array('Book', $formats)) {
            return 'Book';
        } elseif (in_array('Article', $formats)) {
            return 'Article';
        } elseif (in_array('Journal', $formats)) {
            return 'Journal';
        } elseif (isset($formats[0])) {
            return $formats[0];
        }
        return 'UnknownFormat';
    }

    /**
     * Get OpenURL parameters for a book.
     *
     * @return array
     */
    protected function getBookOpenUrlParams()
    {
        $params = $this->getDefaultOpenUrlParams();
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
        $params['rft.genre'] = 'book';
        $params['rft.btitle'] = $params['rft.title'];
        $series = self::getSeries();
        if (count($series) > 0) {
            // Handle both possible return formats of getSeries:
            $params['rft.series'] = is_array($series[0]) ?
                $series[0]['name'] : $series[0];
        }
        $params['rft.au'] = $this->getPrimaryAuthor();
        $publishers = $this->getPublishers();
        if (count($publishers) > 0) {
            $params['rft.pub'] = $publishers[0];
        }
        $params['rft.edition'] = $this->getEdition();
        $params['rft.isbn'] = (string)$this->getCleanISBN();
        return $params;
    }

    public function getSetMultiPart()
    {
        $ids = $this->getHierarchyParentID();
        if (empty($ids)) {
            return [];
        }
        $titles = $this->getHierarchyParentTitle();
        $result = [];
        if (count($ids) === count($titles)) {
            foreach ($ids as $key => $id) {
                $result[] = [
                    'text' => $titles[$key],
                    'id' => $id,
                    'identifier' => 'Set Multipart'
                ];
            }
        }
        return $result;
    }

    /**
     * Get the hierarchy_parent_id(s) associated with this item (empty if none).
     *
     * @return array
     */
    public function getHierarchyParentID()
    {
        return isset($this->fields['hierarchy_parent_id'])
            ? $this->fields['hierarchy_parent_id'] : [];
    }

    /**
     * Get the parent title(s) associated with this item (empty if none).
     *
     * @return array
     */
    public function getHierarchyParentTitle()
    {
        return isset($this->fields['hierarchy_parent_title'])
            ? $this->fields['hierarchy_parent_title'] : [];
    }

    /**
     * Gets ansigel date of the record
     *
     * @return string
     */
    public function getDateIsil()
    {
        // check if there's an extension defined for the library depended format
        // index field
        $isExtension = (isset($this->mainConfig->CustomIndex->indexExtension))
            ? true : false;

        $date = (true === $isExtension)
            ? 'date_' . $this->mainConfig->CustomIndex->indexExtension : '';

        return isset($this->fields[$date]) ? $this->fields[$date] : '';
    }

    /**
     * Get the barcode from the institution relevant barcode_{isil} field
     *
     * @return array
     */
    public function getBarcode()
    {
        if (isset($this->mainConfig->CustomIndex->indexExtension)) {
            return isset($this->fields['barcode_' . ($this->mainConfig->CustomIndex->indexExtension)])
                ? $this->fields['barcode_' . ($this->mainConfig->CustomIndex->indexExtension)] : [];
        }
        return [];
    }

    /**
     * Get the formats for displaying the icons. Renders the format information to
     * a specific css class.
     * There are two setups possible. If combinedIcons sets to true at config.ini
     * all format values will be concatenated to one string; if it's false only
     * the first vlaue will be taken.
     *
     * @internal            Should be moved out of RecordDriver (Controller/View?)
     * @todo                Should be moved out of RecordDriver (Controller/View?)
     *
     * @return array
     */
    /*    protected function getFormatIcon()
        {
            global $configArray;

            $format = $this->getFormats();
            // check which method to build the css class is chosen
            if (isset($this->mainConfig->Site->combinedIcons) && true == $this->mainConfig->Site->combinedIcons) {
                // sort it
                sort($format, SORT_LOCALE_STRING);
                return strtolower(implode('', $format));
                // otherwise take the first format
            } else {
                if (isset($this->fields['multipart_set'])) {
                    switch ($this->fields['multipart_set']) {
                        case 'a': return 'sets';
                        case 'b': break; //return 'part-related';
                        case 'c': break; //return 'part-not-related';
                    }
                }
                //echo "<pre>"; print_r($format); echo "</pre>";
                return $format[0];
            }
        }*/

    /**
     * Get the source id of the record.
     *
     * @return string
     */
    public function getSourceID()
    {
        return isset($this->fields['source_id']) ?
            $this->fields['source_id'] : '';
    }

    /**
     * Get an array of all mega_collections in the record.
     *
     * @return array
     */
    public function getMegaCollection()
    {
        return isset($this->fields['mega_collection']) ? $this->fields['mega_collection'] : [];
    }

    /**
     * Get the content of field multipart_set.
     *
     * @return string
     */
    public function getMultiPart()
    {
        return isset($this->fields['multipart_set']) ? $this->fields['multipart_set'] : '';
    }

    /**
     * Get the GND of an author.
     *
     * @return array
     */
    public function getAuthorId()
    {
        return isset($this->fields['author_id']) ?
            $this->fields['author_id'] : [];
    }

    /**
     * Combined fields of author data.
     *
     * @return array
     * @link https://intern.finc.info/issues/1866
     */
    public function getCombinedAuthors()
    {
        $retval = [];

        $buildCombined = function ($authors, $authorsOrig) use (&$retval) {
            if (count($authors)) {
                foreach ($authors as $key => $value) {
                    $retval[] = $value . (
                            isset($authorsOrig[$key])
                                ? '(' . $authorsOrig[$key] . ')' : ''
                        );
                }
            }
        };

        // use self:: referenced methods to make sure we are not using SolrMarc
        // methods
        if (count(self::getPrimaryAuthors())) {
            $buildCombined(
                (array)self::getPrimaryAuthors(),
                (array)self::getPrimaryAuthorsOrig()
            );
        } elseif (count(self::getCorporateAuthors())) {
            $buildCombined(
                (array)self::getCorporateAuthors(),
                (array)self::getCorporateAuthorsOrig()
            );
        } elseif (count(self::getSecondaryAuthors())) {
            $buildCombined(
                self::getSecondaryAuthors(),
                self::getSecondaryAuthorsOrig()
            );
        } elseif (count(self::getCorporateSecondaryAuthors())) {
            $buildCombined(
                self::getCorporateSecondaryAuthors(),
                self::getCorporateSecondaryAuthorsOrig()
            );
        }

        return $retval;
    }

    /**
     * Get the default value if no original name is available
     *
     * @return string
     */
    protected function getDefaultOrigName()
    {
        //TODO: make this configurable - aka get value from config!
        return 'noOrigName';
    }

    /**
     * Get the main author of the record.
     *
     * @return string
     * @deprecated
     */
    public function getPrimaryAuthor()
    {
        return $this->_filterAuthorDates(parent::getPrimaryAuthor());
    }

    /**
     * Get the main authors of the record.
     *
     * @return array
     */
    public function getPrimaryAuthors()
    {
        return $this->_filterAuthorDates(parent::getPrimaryAuthors());
    }

    /**
     * Get the original authors of the record.
     *
     * @return array
     */
    public function getPrimaryAuthorsOrig()
    {
        return isset($this->fields['author_orig']) ?
            $this->_filterAuthorDates($this->fields['author_orig']) : [];
    }

    /**
     * Get an array of all secondary authors
     *
     * @return array
     */
    public function getSecondaryAuthors()
    {
        return $this->_filterAuthorDates(parent::getSecondaryAuthors());
    }

    /**
     * Get an array of all secondary authors original name (complementing
     * getPrimaryAuthorOrig()).
     *
     * @return array
     */
    public function getSecondaryAuthorsOrig()
    {
        return isset($this->fields['author2_orig']) ?
            $this->_filterAuthorDates($this->fields['author2_orig']) : [];
    }

    /**
     * Get the secondary corporate authors (if any) for the record.
     *
     * @return array
     */
    public function getCorporateAuthors()
    {
        return $this->_filterAuthorDates(parent::getCorporateAuthors());
    }

    /**
     * Get the main corporate authors original name (if any) for the record.
     *
     * @return array
     */
    public function getCorporateAuthorsOrig()
    {
        return isset($this->fields['author_corporate_orig']) ?
            $this->_filterAuthorDates($this->fields['author_corporate_orig']) : [];
    }

    /**
     * Get the secondary corporate authors (if any) for the record.
     *
     * @return array
     */
    public function getCorporateSecondaryAuthors()
    {
        return isset($this->fields['author_corporate2']) ?
            $this->_filterAuthorDates($this->fields['author_corporate2']) : [];
    }

    /**
     * Get the secondary corporate authors original name (if any) for the record.
     *
     * @return array
     */
    public function getCorporateSecondaryAuthorsOrig()
    {
        return isset($this->fields['author_corporate2_orig']) ?
            $this->_filterAuthorDates($this->fields['author_corporate2_orig']) : [];
    }

    /**
     * Get an array of all main corporate authors roles.
     *
     * @return array
     */
    public function getCorporateSecondaryAuthorsRoles()
    {
        return isset($this->fields['author_corporate2_role']) ?
            $this->fields['author_corporate2_role'] : [];
    }

    /**
     * Deduplicate author information into associative array with main/main_orig/
     * corporate/corporate_orig/corporate_secondary/corporate_secondary_orig/
     * secondary/secondary_orig keys.
     *
     * @param array $dataFields
     *
     * @return array
     */
    public function getDeduplicatedAuthors($dataFields = [])
    {
        // use self:: referenced methods to make sure we are not using SolrMarc
        // methods
        $authors = [
            'primary' => self::getAuthorRolesArray(
                self::getPrimaryAuthors(),
                self::getPrimaryAuthorsRoles()
            ),
            'primary_orig' => self::getAuthorOrigArray(
                self::getPrimaryAuthors(),
                self::getPrimaryAuthorsOrig()
            ),
            'corporate' => self::getAuthorRolesArray(
                self::getCorporateAuthors(),
                self::getCorporateAuthorsRoles()
            ),
            'corporate_orig' => self::getAuthorOrigArray(
                self::getCorporateAuthors(),
                self::getCorporateAuthorsOrig()
            ),
            'corporate_secondary' => self::getAuthorRolesArray(
                self::getCorporateSecondaryAuthors(),
                self::getCorporateSecondaryAuthorsRoles()
            ),
            'corporate_secondary_orig' => self::getAuthorOrigArray(
                self::getCorporateSecondaryAuthors(),
                self::getCorporateSecondaryAuthorsOrig()
            ),
            'secondary' => self::getAuthorRolesArray(
                self::getSecondaryAuthors(),
                self::getSecondaryAuthorsRoles()
            ),
            'secondary_orig' => self::getAuthorOrigArray(
                self::getSecondaryAuthors(),
                self::getSecondaryAuthorsOrig()
            )
        ];

        $all_empty = true;
        foreach ($authors as $type => $values) {
            if (!empty($values)) {
                $all_empty = false;
                break;
            }
        }
        if ($all_empty) {
            return [];
        }

        // deduplicate
        $dedup = function (&$array1, &$array2) {
            if (!empty($array1) && !empty($array2)) {
                foreach ($array1 as $author => $roles) {
                    if (isset($array2[$author])) {
                        $array1[$author] = array_merge(
                            $array1[$author],
                            $array2[$author]
                        );
                        unset($array2[$author]);
                    }
                }
            }
        };

        $dedup($authors['corporate'], $authors['corporate_secondary']);
        $dedup($authors['primary'], $authors['corporate']);
        $dedup($authors['secondary'], $authors['corporate']);
        $dedup($authors['primary'], $authors['secondary']);

        // do the same dedup for author arrays with orig names
        $dedup($authors['corporate_orig'], $authors['corporate_secondary_orig']);
        $dedup($authors['primary_orig'], $authors['corporate_orig']);
        $dedup($authors['secondary_orig'], $authors['corporate_orig']);
        $dedup($authors['primary_orig'], $authors['secondary_orig']);

        $dedup_roles = function (&$array) {
            foreach ($array as $author => $roles) {
                if (is_array($roles)) {
                    $array[$author] = array_unique($roles);
                }
            }
        };

        $dedup_roles($authors['primary']);
        $dedup_roles($authors['secondary']);
        $dedup_roles($authors['corporate']);
        $dedup_roles($authors['corporate_secondary']);

        // we can use $dedup_roles to dedup the orig names as both arrays have the
        // same structure
        $dedup_roles($authors['primary_orig']);
        $dedup_roles($authors['secondary_orig']);
        $dedup_roles($authors['corporate_orig']);
        $dedup_roles($authors['corporate_secondary_orig']);

        return $authors;
    }

    /**
     * Helper function to restructure author arrays including original names
     *
     * @param array $authors   Array of authors
     * @param array $orignames Array with original names of authors
     *
     * @return array
     */
    protected function getAuthorOrigArray($authors = [], $orignames = [])
    {
        $authorOrigArray = [];

        if (!empty($authors)) {
            foreach ($authors as $index => $author) {
                if (!isset($authorOrigArray[$author])) {
                    $authorOrigArray[$author] = [];
                }
                if (isset($orignames[$index]) && !empty($orignames[$index])
                ) {
                    $authorOrigArray[$author][] = $orignames[$index];
                }
            }
        }

        return $authorOrigArray;
    }

    /**
     * Get the field-value identified by $string
     *
     * @param string $string Name of field
     *
     * @return string
     */
    public function getILSIdentifier($string)
    {
        return isset($this->fields[$string]) ? $this->fields[$string] : '';
    }

    /**
     * Get an array of all ISMNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISMNs()
    {
        return isset($this->fields['ismn']) && is_array($this->fields['ismn']) ?
            $this->fields['ismn'] : [];
    }

    /**
     * Get the eISSN from a record.
     *
     * @return array
     */
    public function getEISSNs()
    {
        return [];
    }

    /**
     * Get an array of newer titles for the record.
     *
     * @return array
     */
    public function getNewTitles()
    {
        return isset($this->fields['title_new']) ?
            $this->fields['title_new'] : [];
    }

    /**
     * After giving a record ids as e.g. ppn of the BSZ check if a record exists.
     * This method can be used to indicate a direct link than to form a general
     * look for query.
     *
     * @param array $array Array of record ids to test.
     *
     * @return int mixed  If success return at least one finc id otherwise null.
     */
    protected function addFincIDToRecord($array)
    {
        // record ids
        $rids = [];
        // return array
        $retval = [];

        // check if array contain record_ids and collect it as an array to
        // use only one solr request for all
        if (isset($array) && is_array($array)) {
            foreach ($array as $line) {
                if (isset($line['record_id']) && isset($line['source_id'])) {
                    $rids[] = [
                        'rid' => $line['record_id'],
                        'sid' => $line['source_id']
                    ];
                }
            }
        }

        // build the query:
        if (count($rids) == 1) {
            // single query:
            $value = 'record_id:' . $rids[0]['rid']
                     . ' AND source_id:' . $rids[0]['sid'];
        } elseif (count($rids) > 1) {
            // multi query:
            foreach ($rids as $rid) {
                $parts[] = '(record_id:' . $rid['rid']
                           . ' AND source_id:' . $rid['sid'] . ')';
            }
            $value = '(' . implode(' OR ', $parts) . ')';
        } else {
            return $array;
        }
        $query = new \VuFindSearch\Query\Query($value);
        $bag = new ParamBag();
        $bag->set('fl', 'id,record_id');
        $records =  $this->searchService
            ->search('Solr', $query, 0, count($rids), $bag);

        $records = $records->getRecords();
        if (isset($records)
            && !empty($records)
        ) {
            foreach ($records as $record) {
                $retval[$record->getRID()] = $record->getUniqueID();
            }
        }

        // write back in array
        foreach ($array as &$val) {
            if (isset($val['record_id'])) {
                if (isset($retval[($val['record_id'])])) {
                    $val['id'] = $retval[($val['record_id'])];
                }
            }
        }
        unset($val);

        return $array;
    }

    /**
     * Get the recordtype of the current Record
     *
     * @return string
     */
    public function getRecordType()
    {
        return isset($this->fields['recordtype']) ?
            $this->fields['recordtype'] : '';
    }

    /**
     * Get percentage of relevance of a title. First implementaion for TUBAF.
     *
     * @return float        Percentage of Score / Maximum Score rounded by 5.
     * @link   https://intern.finc.info/issues/1908
     */
    public function getRelevance()
    {
        $score = isset($this->fields['score']) ? $this->fields['score'] : 0;
        $maxScore = isset($this->fields['score_maximum']) ? $this->fields['score_maximum'] : 0;

        if ($score == 0 || $maxScore == 0) {
            return 0;
        }
        return round(($score / $maxScore), 5);
    }

    /**
     * Get RVK classifcation number from Solr index.
     *
     * @return string
     */
    public function getRvk()
    {
        return isset($this->fields['rvk_facet']) ?
            $this->fields['rvk_facet'] : '';
    }

    /**
     * Get special record_id of libero system.
     *
     * @todo    refactor to a more meaningful name?
     *
     * @return string
     */
    public function getRID()
    {
        return isset($this->fields['record_id']) ?
            $this->fields['record_id'] : '';
    }

    /**
     * Get the original title of the record.
     *
     * @return string
     */
    public function getTitleOrig()
    {
        return isset($this->fields['title_orig']) ?
            $this->fields['title_orig'] : '';
    }

    /**
     * Get the original title of the record.
     *
     * @return string
     */
    public function getTitleUniform()
    {
        return isset($this->fields['title_uniform']) ?
            $this->fields['title_uniform'] : '';
    }

    /**
     * Get an array of title detail lines with original notations combining
     * information from MARC field 245 and linked content in 880.
     *
     * @return array
     */
    public function getTitleDetails()
    {
        return [
            implode(' ', [$this->getTitle(), $this->getSubtitle(), $this->getTitleStatement()]),
            implode(' ', [$this->getTitleOrig(), $this->getTitleStatementOrig()])
        ];
    }

    /**
     * Get the original statement of responsibility that goes with the title (i.e.
     * "by John Smith").
     *
     * @return string
     */
    public function getTitleStatementOrig()
    {
        // not supported right now
        return null;
    }

    /**
     * Get the GND of topic.
     *
     * @return array
     */
    public function getTopicId()
    {
        return isset($this->fields['topic_id']) ?
            $this->fields['topic_id'] : [];
    }

    /**
     * Get an array of all series names containing the record.  Array entries may
     * be either the name string, or an associative array with 'name' and 'number'
     * keys.
     *
     * @return array
     */
    public function getSeries()
    {
        $retval = [];

        // Only use the contents of the series2 field if the series field is empty
        if (isset($this->fields['series']) && !empty($this->fields['series'])) {
            $retval = $this->fields['series'];
        }
        return array_merge(
            $retval,
            $this->getSeriesAlternative(),
            $this->getSeriesOrig()
        );
    }

    /**
     * Get alternatives series titles as array.
     *
     * @return array
     */
    public function getSeriesAlternative()
    {
        if (isset($this->fields['series2']) && !empty($this->fields['series2'])) {
            return $this->fields['series2'];
        }
        return [];
    }

    /**
     * Get alternatives series titles as array.
     *
     * @return array
     */
    public function getSeriesOrig()
    {
        if (isset($this->fields['series_orig']) && !empty($this->fields['series_orig'])) {
            return $this->fields['series_orig'];
        }
        return [];
    }

    /**
     * Gets sorted publication date as string
     *
     * @return string
     */
    public function getPublishDateSort()
    {
        return isset($this->fields['publishDateSort']) ?
            $this->fields['publishDateSort'] : '';
    }

    /**
     * Get a precompiled string of publication details stored in the Solr field
     * imprint.
     *
     * @return string
     */
    public function getImprint()
    {
        return isset($this->fields['imprint']) ?
            $this->fields['imprint'] : '';
    }

    /**
     * Get the item's place of publication.
     *
     * @return array
     */
    public function getPlacesOfPublication()
    {
        return isset($this->fields['publishPlace']) ?
            $this->fields['publishPlace'] : [];
    }

    /**
     * Get value of access_facet field
     *
     * @return string
     */
    public function getAccessFacet()
    {
        return isset($this->fields['access_facet'])
            ? $this->fields['access_facet'] : '';
    }

    /**
     * Get specific marc information about additional items. Unflexible solution
     * for UBL only implemented.
     *
     * @return array
     * @link https://intern.finc.info/fincproject/issues/1315
     */
    public function getAdditionals()
    {
        return [];
    }

    /**
     * Get specific marc information about topics. Unflexible solution
     * for UBL only implemented.
     *
     * @return array
     */
    public function getTopics()
    {
        return array_merge($this->getAllSubjectHeadings());
    }

    /**
     * Check if Additional Items exists. Realized for instance of UBL only.
     *
     * @return boolean      True if additional items exists.
     * @link https://intern.finc.info/fincproject/issues/1315
     */
    public function hasAdditionalItems()
    {
        $array = $this->getAdditionals();
        return (is_array($array) && count($array) > 0) ? true : false;
    }

    /**
     * Check if Topics exists. Realized for instance of UBL only.
     *
     * @return boolean      True if topics exist.
     */
    public function hasTopics()
    {
        $array = $this->getTopics();
        return (is_array($array) && count($array) > 0) ? true : false;
    }

    /**
     * Filter author data for author year of birth and death
     * to give a better mark up.
     *
     * @param string $authordata
     *
     * @return array
     */
    private function _filterAuthorDates($authordata)
    {
        $filter = function ($author) {
            if (preg_match('/^(\s|.*)(\d{4})\s?-?\s?(\d{4})?$/Uu', $author, $match)) {
                return (isset($match[3]))
                    ? $match[1] . ' *' . $match[2] . '-†' . $match[3]
                    : $match[1] . ' *' . $match[2] . '-';
            }
            return $author;
        };

        if (is_array($authordata)) {
            $retval = [];
            foreach ($authordata as $author) {
                $retval[] = $filter($author);
            }
            return $retval;
        } else {
            return $filter($authordata);
        }
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
        return ['APA', 'ISBD', 'MLA'];
    }

    /**
     * Return content of Solr field zdb if set
     *
     * @return mixed
     */
    public function getZdbId()
    {
        return isset($this->fields['zdb']) ?
            $this->fields['zdb'] : null;
    }

    /**
     * Checks the record for having no hierarchy children. Returns true if record is
     * top element of hierarchy and has no children.
     *
     * @return bool
     */
    public function isSingleElementHierarchyRecord()
    {
        $hierId = $this->getHierarchyTopID();
        $currId = $this->getUniqueID();

        // is the record's id indexed as its hierarchy_top_id
        if (in_array($currId, $hierId)) {
            $query = 'hierarchy_top_id:' . $currId;
            $result = $this->searchService->search('Solr', new Query($query));
            if (count($result) === 0) {
                // for debugging only
                $this->debug(
                    'Problem retrieving total number of records with ' .
                    'hierarchy_top_id ' . $currId
                );
            }
            // number of records
            $numFound = count($result->getRecords());
            if ($numFound > 1) {
                return false;
            }
        }

        // either record is no top element of any hierarchy or we have come so far
        // because it's the only element of its hierarchy
        return true;
    }

    /**
     * Return content of Solr field performer_note if set
     *
     * @return mixed
     */
    public function getPerformerNote()
    {
        return isset($this->fields['performer_note']) ?
            $this->fields['performer_note'] : null;
    }

    /**
     * Return content of Solr field music_heading if set
     *
     * @return mixed
     */
    public function getMusicHeading()
    {
        return isset($this->fields['music_heading']) ?
            $this->fields['music_heading'] : null;
    }

    /**
     * Return content of Solr field title_alt if set
     *
     * @return mixed
     */
    public function getTitleAlt()
    {
        return isset($this->fields['title_alt']) ?
            $this->fields['title_alt'] : null;
    }

    /**
     * Get values of genre_facet field
     *
     * @return array
     */
    public function getGenreFacet()
    {
        return isset($this->fields['genre_facet'])
            ? $this->fields['genre_facet'] : [];
    }

    /**
     * Get values of facet_avail field
     *
     * @return array
     */
    public function getFacetAvail()
    {
        return isset($this->fields['facet_avail'])
            ? $this->fields['facet_avail'] : [];
    }
}
