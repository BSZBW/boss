<?php
/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */
namespace Bsz\RecordDriver;

use Bsz\FormatMapper;

/**
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGvimarc extends SolrMarc
{
    use \VuFind\RecordDriver\IlsAwareTrait;
    use \VuFind\RecordDriver\MarcReaderTrait;
    use \VuFind\RecordDriver\MarcAdvancedTrait;

    /**
     *
     * @var FormatMapper
     */
    protected $mapper;

    /**
     *
     * @var Bsz\Config\Client
     */
    protected $client;

    /**
     *
     * @var array
     */
    protected $container = [];

    /**
     *
     * @var array
     */
    protected $formats;

    public function __construct(FormatMapper $Mapper, \Bsz\Config\Client $Client,
            $mainConfig = null, $recordConfig = null, $searchSettings = null)
    {
//        parent::__construct($mainConfig, $recordConfig, $searchSettings);
        parent::__construct($Mapper, $mainConfig, $recordConfig, $searchSettings);
        $this->mapper = $Mapper;
        $this->client = $Client;
    }

    /**
     * Get subject headings associated with this record.  Each heading is
     * returned as an array of chunks, increasing from least specific to most
     * specific.
     *
     * @return array
     */
    public function getSubjectHeadings(array $fields)
    {
        // This is all the collected data:
        $retval = array();

        // Try each MARC field one at a time:
        foreach ($fields as $field) {
            // Do we have any results for the current field?  If not, try the next.
            $results = $this->getMarcRecord()->getFields($field);
            if (!$results) {
                continue;
            }

            // If we got here, we found results -- let's loop through them.
            foreach ($results as $result) {
                // Start an array for holding the chunks of the current heading:
                $current = array();

                // Get all the chunks and collect them together:
                $subfields = $result->getSubfields();
                if ($subfields) {
                    foreach ($subfields as $subfield) {
                        // Numeric subfields are for control purposes and should not
                        // be displayed:
                        if (!is_numeric($subfield->getCode())) {
                            $current[] = $subfield->getData();
                        }
                    }
                    // If we found at least one chunk, add a heading to our result:
                    if (!empty($current)) {
                        $retval[] = $current;
                    }
                }
            }
        }

        // Send back everything we collected:
        return $retval;
    }

    /**
     * Get all subject headings associated with this record.  Each heading is
     * returned as an array of chunks, increasing from least specific to most
     * specific.
     *
     * @return array
     */
    public function getAllSubjectHeadings($extended = false)
    {
        // These are the fields that may contain subject headings:
        $fields = ['600', '610', '611', '630', '648', '650', '651', '655',
            '656', '689', '936'];
        $headings = $this->getSubjectHeadings($fields);
        return $headings;
//        if(array_key_exists('subject_all', $this->fields)) {
//            return $this->fields['subject_all'];
//        }
//        else {
//            return array();
//        }
    }

    /**
     * Get SWD subjects.     *
     * @return array
     */
    public function getAllSWDSubjectHeadings()
    {
        $swdchain = [];
        foreach ($this->getMarcRecord()->getFields('689') as $field) {
            $ind1 = $field->getIndicator(1);
            $ind2 = $field->getIndicator(2);
            if (is_numeric($ind1) && is_numeric($ind2)) {
                $field = $field->getSubField('a');
                if ($field) {
                    $swdchain[$ind1][] = $field->getData();
                }
            }
        }
        return $swdchain;
    }

    /**
     * Get all subjects associated with this item. They are unique.
     *
     * @return array
     */
    public function getAllRVKSubjectHeadings()
    {
        // Disable this output
        return [];
        $rvkchain = [];
        foreach ($this->getMarcRecord()->getFields('936') as $field) {
            foreach ($field->getSubFields('k') as $item) {
                $rvkchain[] = $item->getData();
            }
        }
        return array_unique($rvkchain);
    }

    /**
     * Get an array with RVK shortcut as key and description as value (array)
     * @returns array
     */
    public function getRVKNotations()
    {
        $notationList = [];
        $replace = [
            '"' => "'",
        ];
        foreach ($this->getMarcRecord()->getFields('084') as $field) {
            $suba = $field->getSubField('a');
            $sub2 = $field->getSubfield('2');
            if ($suba && strtolower($sub2) == 'rvk') {
                $title = [];
                foreach ($field->getSubFields('k') as $item) {
                    $title[] = htmlentities($item->getData());
                }
                $notationList[$suba->getData()] = $title;
            }
        }
        foreach ($this->getMarcRecord()->getFields('936') as $field) {
            $suba = $field->getSubField('a');
            if ($suba) {
                $title = [];
                foreach ($field->getSubFields('k') as $item) {
                    $title[] = htmlentities($item->getData());
                }
                $notationList[$suba->getData()] = $title;
            }
        }
        return $notationList;
    }

    /**
     * Get the call number associated with the record (empty string if none).
     *
     * @return string
     */
    public function getCallNumber()
    {
        return $this->getPPN();
    }

    /**
     * Get the date coverage for a record which spans a period of time (i.e. a
     * journal).  Use getPublicationDates for publication dates of particular
     * monographic items.
     *
     * @return array
     */
    public function getDateSpan()
    {
        return $this->getFieldArray('362', ['a']);
    }

    /**
     * Get the edition of the current record.
     *
     * @return string
     */
    public function getEdition()
    {
        return $this->getFirstFieldValue('250', ['a']);
    }

    /**
     * Get a string representing the last date that the record was indexed.
     *
     * @return string
     */
    public function getLastIndexed()
    {
        return isset($this->fields['last_indexed']) ? $this->fields['last_indexed'] : '';
    }

    /**
     * Get the institutions holding the record.
     *
     * @return array
     */
    public function getInstitutions()
    {
        return $this->getFieldArray('924', ['b'], false);
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISBNs()
    {
        //isbn = 020az:773z
        $isbn = array_merge(
                $this->getFieldArray('020', ['a', 'z', '9'], false),
                $this->getFieldArray('773', ['z'])
        );
        return $isbn;
    }

    /**
     * Get an array of all ISSNs associated with the record (may be empty).
     *
     * @return array
     */
    public function getISSNs()
    {
        // issn = 022a:440x:490x:730x:773x:776x:780x:785x
        $issn = array_merge(
                $this->getFieldArray('022', ['a']),
                $this->getFieldArray('029', ['a']),
                $this->getFieldArray('440', ['x']),
                $this->getFieldArray('490', ['x']),
                $this->getFieldArray('730', ['x']),
                $this->getFieldArray('773', ['x']),
                $this->getFieldArray('776', ['x']),
                $this->getFieldArray('780', ['x']),
                $this->getFieldArray('785', ['x'])
        );
        return $issn;
    }

    /**
     * Get just the base portion of the first listed ISSN (or false if no ISSNs).
     *
     * @return mixed
     */
    public function getCleanISSN()
    {
        $issns = $this->getISSNs();
        if (empty($issns)) {
            return false;
        }
        $issn = $issns[0];
        if ($pos = strpos($issn, ' ')) {
            $issn = substr($issn, 0, $pos);
        }
        // ISSN without dash are treatened as invalid be JOP
        if (strpos($issn, '-') === false) {
            $issn = substr($issn, 0, 4).'-'.substr($issn, 4, 4);
        }
        return $issn;
    }

    /**
     * Get an array of all the languages associated with the record.
     *
     * @return array
     */
    public function getLanguages()
    {
        $languages = [];
        $fields = $this->getMarcRecord()->getFields('041');
        foreach ($fields as $field) {
                foreach ($field->getSubFields('a') as $sf) {
                    $languages[] = $sf->getData();
                }
        }
        return $languages;
    }

    /**
     * Get a LCCN, normalised according to info:lccn
     *
     * @return string
     */
    public function getLCCN()
    {
        //lccn = 010a, first
        return $this->getFirstFieldValue('010', ['a']);
    }

    /**
     * Get an array of newer titles for the record.
     *
     * @return array
     */
    public function getNewerTitles()
    {
        //title_new = 785ast
        return $this->getFieldArray('785', ['a', 's', 't']);
    }

    /**
     * Get the OCLC number of the record.
     *
     * @return array
     */
    public function getOCLC()
    {
        $numbers = [];
        $pattern = '(OCoLC)';
        foreach ($this->getFieldArray('016') as $f) {
            if (!strncasecmp($pattern, $f, strlen($pattern))) {
                $numbers[] = substr($f, strlen($pattern));
            }
        }
        return $numbers;
    }

    /**
     * Get an array of physical descriptions of the item.
     *
     * @return array
     */
    public function getPhysicalDescriptions()
    {
        return $this->getFieldArray('300', ['a', 'b', 'c', 'e', 'f', 'g'], true);
    }

    /**
     * Get PPN of Record
     *
     * @return string
     */
    public function getPPN()
    {
        return $this->getMarcRecord()->getField('001')->getData();
    }

    /**
     * Get an array of previous titles for the record.
     *
     * @return array
     */
    public function getPreviousTitles()
    {
        //title_old = 780ast
        return $this->getFieldArray('780', ['a', 's', 't']);
    }

    /**
     * Get the main author of the record.
     *
     * @return string
     */
    public function getPrimaryAuthor()
    {
        $author = trim($this->getFirstFieldValue('100', ['a']));
        $titles = trim($this->getFirstFieldValue('100', ['c']));
        $dates = trim($this->getFirstFieldValue('100', ['d']));

        if (!empty($titles)) {$author .= ', ' . $titles;}
        if (!empty($dates)) {$author .= ', ' . $dates;}

        return $author;

    }
    
    /**
     * Get an Array of Author Name with Live Data
     * 
     * @return array
     */
    public function getPrimaryAuthorNoLive()
    {
        $nolive_author = trim($this->getFirstFieldValue('100', ['a']));
        return $nolive_author;
    }
    
    /**
     * returns all authors from 100 or 700 without life data
     * @return array
     */
    public function getAllAuthorsShort() 
    {
        $authors = array_merge(
            $this->getFieldArray('100', ['a', 'b']),
            $this->getFieldArray('700', ['a', 'b'])
        );
        return array_unique($authors);
    }

    /**
     * Get GND-ID from 100|0 with (DE-588)-prefix
     *
     * @return string
     */
    public function getPrimaryAuthorGND()
    {
        $gndauthor = '';

        $candidates = $this->getFieldArray('100', ['0'], false);
        foreach ($candidates as $item) {
            if (strpos($item, '(DE-588)') !== FALSE) {
                $gndauthor = $item;
                break;
            }
        }
        return $gndauthor;
    }
    
    /**
     * Get GND-ID from 700|0 with (DE-588)-prefix
     *
     * @return array
     */
    public function getSecondaryAuthorGND()
    {
        $gndauthor = [];

        $candidates = $this->getFieldArray('700', ['0'], false);
        
        foreach ($candidates as $item) {
            if (strpos($item, '(DE-588)') !== FALSE) {
                $gndauthor[] = $item;                
            }
        }
        return $gndauthor;
    }


    /**
     * Get the item's place of publication.
     *
     * @return array
     */
    public function getPlacesOfPublication()
    {
        $fields = [
            260 => 'a',
            264 => 'a',
        ];
        $places = $this->getFieldsArray($fields);
        foreach ($places as $k => $place) {
            $replace = [' :'];
            $places[$k] = str_replace($replace, '', $place);
        }
        return array_unique($places);
    }

    /**
     * Get the publishers of the record.
     *
     * @return array
     */
    public function getPublishers()
    {
        $fields = [
            260 => 'b',
            264 => 'b',
        ];
        return $this->getFieldsArray($fields);
    }

    /**
     * Get the publication dates of the record.  See also getDateSpan().
     *
     * @return array
     */
    public function getPublicationDates()
    {
        $return = [];
        $years = [];
        $f008 = $this->getMarcRecord()->getField('008');
        $matches = [];
        if (is_object($f008)) {
            $f008 = $f008->getData();
            preg_match('/^(\d{2})(\d{2})(\d{2})([a-z])(\d{4})/', $f008, $matches);
        }
        if (array_key_exists(5, $matches)) {
            $years[] = $matches[5];
        }
        // if there's still no year, we parse it out of 260'
        if (count($years) == 0) {
            $fields= [
                260 => 'c',
                264 => 'c',
            ];
            $years = $this->getFieldsArray($fields);

            foreach ($years as $k => $year) {
                if ($year == 'anfangs' || $year == 'früher' || $year == 'teils') {
                    unset($years[$k]);
                } else {
                    // this magix removes braces and other chars
                    $years[$k] = preg_replace('/[^\d-]|-$/', '', $year);
                }
            }


        }
        if (count($years) > 0) {
            $return = array_values(array_unique($years));
        }
        return $return;

    }

    /**
     * Get an array of all secondary authors (complementing getPrimaryAuthor()).
     *
     * @return array
     */
        public function getSecondaryAuthors()
    {
        $author2 = $this->getFieldArray('700', ['a', 'b', 'c', 'd']);
        return $author2;
    }
    
    /**
     * Get an Array of Author Names with Live Data
     * they need to be translated. 
     *
     * @return array
     */
    public function getSecondaryAuthorsNoLive()
    {
        $nolive_author2 = $this->getFieldArray('700', ['a']);
        return $nolive_author2;
    }

    /**
     * Get an Array of Author roles
     * they need to be translated. 
     *
     * @return array
     */
    public function getSecondaryAuthorsRole()
    {
        $author2 = $this->getFieldArray('700', ['4']);
        return $author2;
    }
    
    public function getCorporateAuthors() {
        $corporate = array_merge(
            $this->getFieldArray('110', ['a', 'b']),// corporate
            $this->getFieldArray('111', ['a', 'b']),// Meeting
            $this->getFieldArray('710', ['a', 'b']),// corporate
            $this->getFieldArray('711', ['a', 'b']) // Meeting
        );
        return $corporate;
    }

    /**
     * Return an array of primary and secondary authors
     * @return array
     */
    public function getDeduplicatedAuthors($dataFields = ['role'])
    {
        $authors = [];
        $authors['main'] = $this->getPrimaryAuthor();

        $corporate = [];
        $secondary = [];
        foreach (array_unique($this->getSecondaryAuthors()) as $a) {
            if (strpos($authors['main'], $a) === FALSE ) {
                array_push($secondary, $a);
            }
        }
        $authors['secondary'] = array_unique($secondary);
        foreach (array_unique($this->getCorporateAuthors()) as $c) {
            if (strpos($authors['main'], $c) === FALSE) {
                array_push($corporate, $c);
            }
        }
        $authors['corporate'] = array_unique($corporate);

        return $authors;
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
        //series = 440ap:800abcdfpqt:830ap
        //series2 = 490a
        $series = array_merge(
                $this->getFieldArray('440', ['a', 'p']),
                $this->getFieldArray('800', ['a', 'b', 'c', 'd', 'f', 'p', 'q', 't']),
                $this->getFieldArray('830', ['a', 'p']));
        if (count($series) == 0) {
            $series = $this->getFieldArray('490', ['a']);
        }
        return $series;
    }

    /**
     * Get the short (pre-subtitle) title of the record.
     *
     * @return string
     */
    public function getShortTitle()
    {
        $shortTitle = $this->getFirstFieldValue('245', array('a'), false);

        // Sortierzeichen weg
        if (strpos($shortTitle, '@') !== false) {
            $occurrence = strpos($shortTitle, '@');
            $shortTitle = substr_replace($shortTitle, '', $occurrence, 1);
        }
        // remove all non printable chars - they max look ugly in <title> tags
//        $shortTitle = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $shortTitle);

        return $this->cleanString($shortTitle);
    }

    /**
     * Get the subtitle of the record.
     *
     * @return string
     */
    public function getSubtitle()
    {
        $subTitle = $this->getFirstFieldValue('245', array('b'), false);

        // Sortierzeichen weg
        if (strpos($subTitle, '@') !== false) {
            $occurrence = strpos($subTitle, '@');
            $subTitle = substr_replace($subTitle, '', $occurrence, 1);
        }

        return $this->cleanString($subTitle);
    }

    /**
     * Get an array of summary strings for the record.
     *
     * @return array
     */
    public function getSummary()
    {
        $summaryCodes = ['501', '502', '505', '515', '520'];
        $summary = [];
        foreach ($summaryCodes as $sc) {
            $tmp = $this->getFieldArray($sc, ['a', 'b', 'c', 'd'], true, ', ');
            $summary = array_merge($summary, $tmp);
        }
        return $summary;
    }

    /**
     * Returns one of three things: a full URL to a thumbnail preview of the record
     * if an image is available in an external system; an array of parameters to
     * send to VuFind's internal cover generator if no fixed URL exists; or false
     * if no thumbnail can be generated.
     *
     * @param string $size Size of thumbnail (small, medium or large -- small is
     * default).
     *
     * @return string|array|bool
     */
    public function getThumbnail($size = 'small')
    {
        $arr = array();
        $arrSizes = array('small', 'medium', 'large');
        $isbn = $this->getCleanISBN();
        $ean = $this->getEAN();
        if (in_array($size, $arrSizes)) {
            $arr['author'] = $this->getPrimaryAuthor();
        }
        //Books
        if ($isbn || $ean) {
            $arr['size'] = $size;
            $arr['title'] = $this->getTitle();
            $arr['isbn'] = $isbn;
            $arr['ean'] = $ean;
            return $arr;
        }
        //journals and other media  - almost always have no cover
        else {
            return false;
        }
    }

    /**
     * Get the full title of the record.
     *
     * @return string
     */
    public function getTitle()
    {
        $tmp = [
            $this->getShortTitle(),
            ' : ',
            $this->getSubtitle(),
        ];
        $title = implode(' ', $tmp);
        return $this->cleanString($title);
    }

    /**
     * Get the text of the part/section portion of the title.
     *
     * @return string
     */
    public function getTitleSection()
    {
        return $this->getFirstFieldValue('245', array('n', 'p'), false);
    }

    /**
     * Get the statement of responsibility that goes with the title (i.e. "by John
     * Smith").
     *
     * @return string
     */
    public function getTitleStatement()
    {
        return $this->getFirstFieldValue('245', array('c'), false);
    }

    /**
     * Get an array of lines from the table of contents.
     *
     * @return array
     */
    public function getTOC()
    {
        return isset($this->fields['contents']) ? $this->fields['contents'] : array();
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     *
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     *
     * @return array
     */
    public function getURLs()
    {
        //url = 856u:555u

        $urls = [];
        $urlFields = array_merge($this->getMarcRecord()->getFields('856'),
                $this->getMarcRecord()->getFields('555'));
        foreach ($urlFields as $f) {
            $f instanceof File_MARC_Data_Field;
            $url = [];
            $sf = $f->getSubField('u');
            $ind1 = $f->getIndicator(1);
            $ind2 = $f->getIndicator(2);
            if (!$sf) {
                continue;
            }
            $url['url'] = $sf->getData();

            if (($sf = $f->getSubField('3'))) {
                $url['desc'] = $sf->getData();
            } elseif (($sf = $f->getSubField('y'))) {
                $url['desc'] = $sf->getData();
            } elseif ($ind1 == 4 && ($ind2 == 1 || $ind2 == 0)) {
                $url['desc'] = 'Online Access';
            } elseif ($ind1 == 4 && ($ind2 == 1 || $ind2 == 0)) {
                $url['desc'] = 'More Information';
            }
            $urls[] = $url;
        }
        return $urls;
    }

    /* No Hierrachy functions yet */

    /**
     * As out fiels 773 does not contain any further title information we need
     * to query solr again
     *
     * @return array
     */
    public function getContainer()
    {
        if (count($this->container) == 0 &&
            $this->isPart()) {
            $relId = $this->getFieldArray(773, ['w']);
            $this->container = [];
            if (is_array($relId) && count($relId) > 0) {
                foreach ($relId as $k => $id) {
                    $relId[$k] = 'id:"' . $id . '"';
                }
                $params = [
                    'lookfor' => implode(' OR ', $relId),
                ];
                // QnD
                // We need the searchClassId here to get proper filters
                $searchClassId = 'Solr';
//                if (isset($_SERVER['REQUEST_URI']) &&
//                        strpos($_SERVER['REQUEST_URI'], 'Search') !== FALSE) {
//                    $searchClassId = 'Interlending';
//                }
                $results = $this->runner->run($params, $searchClassId);
                $this->container = $results->getResults();
            }
        }
        return $this->container;
    }
    
    public function getContainerId() {
        $fields = [
            773 => ['w'],
        ];
        $array = $this->getFieldsArray($fields);
        return array_shift($array);
    }

    /**
     * Returns ISXN of containing item. ISBN is preferred, if set.
     * @return string
     */
    public function getContainerIsxn() {
         $fields = [
            773 => ['z'],
            773 => ['x'],
        ];
        $array = $this->getFieldsArray($fields);
        return array_shift($array);
    }

    /**
     * Returns ISXN of containing item. ISBN is preferred, if set.
     * @return string
     */
    public function getContainerRelParts() {
         $fields = [
            773 => ['g'],
        ];
        $array = $this->getFieldsArray($fields);
        return array_shift($array);
    }

    /**
     * This function is used to distinguish between articles from journals
     * and articles from books.
     * @return boolean
     */
    public function isContainerMonography()
    {
        // this is applicable only if item is a part of another item
        if ($this->isPart()) {

            $isxn = $this->getContainerIsxn();
            // isbn set
            if (strlen($isxn) > 9) {
                return true;
            } elseif(empty($isxn)) {
                $containers = $this->getContainer();

                if (is_array($containers)) {
                    $container = array_shift($containers);
                    return isset($container) ? $container->isBook() : false;
                }
            }
        }
        return false;
    }


    /**
     * Get the main corporate author (if any) for the record.
     *
     * @return string
     */
    public function getCorporateAuthor()
    {
        // Try 110 first -- if none found, try 710 next.
        $corpAuthors = array_merge($this->getFieldArray('110', array('a', 'b', '9'), true), $this->getFieldArray('710', array('a', 'b', 'g'), true));
        return empty($corpAuthors) ? null : $corpAuthors[0];
    }

    /**
     * Get a sortable title for the record (i.e. no leading articles).
     *
     * @return string
     */
    public function getSortTitle()
    {
        return isset($this->fields['title_sort']) ? $this->fields['title_sort'] : parent::getSortTitle();
    }

    /**
     * Get longitude/latitude text (or false if not available).
     *
     * @return string|bool
     */
    public function getLongLat()
    {
        return isset($this->fields['long_lat']) ? $this->fields['long_lat'] : false;
    }


    /**
     * 
     * @return string
     */
    public function getGroupField()
    {   
        $retval = '';
        if (isset($_SESSION['dedup']['group_field'])) {
            $conf = $_SESSION['dedup']['group_field'];
        } else {
            $conf = $this->client->get('Index')->get('group.field');            
        }
        if (is_string($conf) && isset($this->fields[$conf])) {
            if (is_array($this->fields[$conf])) {
                $retval = array_shift($this->fields[$conf]);
            } else {
                $retval = $this->fields[$conf];
            }
            
        }
        return $retval;
      
    }

    public function getBreadcrumb()
    {
        return $this->cleanString($this->getShortTitle());
    }

    /**
     * Removes colon and slash at the end of the string
     * Removes any HTML
     * @param string $string
     * @return string
     */
    public function cleanString($string)
    {
        $string = trim($string);
        $string = preg_replace('/:$|\/$/', '', $string);
//        $string = strip_tags($string);
        $string = trim($string);
        return $string;
    }

    /**
     * Get an array of information about record holdings, obtained in real-time
     * from the ILS.
     *
     * @return array
     */
    public function getRealTimeHoldings()
    {
        if ($this->client->isIsilSession() && !$this->client->hasIsilSession()) {
            return [];
        } else {
            return $this->hasILS() ? $this->holdLogic->getHoldings(
                            $this->getUniqueID(), $this->getConsortialIDs()
                    ) : [];
        }
        return ['holdings' => []];
    }

    /**
     * On electronic Articles, we do not need to query DAIA.
     * @return boolean
     */
    public function supportsAjaxStatus()
    {
        if ($this->getNetwork() != 'SWB') {
            return false;
        }
        if ($this->client->isIsilSession() && !$this->client->hasIsilSession()) {
            return false;
        }

        if ($this->isArticle() || $this->isEBook() || $this->isSerial() ||
                $this->getMultipartLevel() === static::MULTIPART_COLLECTION) {
            return false;
        }
        return true;
    }


    protected function getBookOpenUrlParams()
    {
        $params = $this->getDefaultOpenUrlParams();
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:book';
        $params['rft.genre'] = 'book';
        $params['rft.btitle'] = $this->getTitle();
        $params['rft.volume'] = $this->getContainerVolume();
        $series = $this->getSeries();
        if (count($series) > 0) {
            // Handle both possible return formats of getSeries:
            $params['rft.series'] = is_array($series[0]) ?
                    $series[0]['name'] : $series[0];
        }
        $authors = $this->getAllAuthorsShort();
        $params['rft.au'] = array_shift($authors);
        $publication = $this->getPublicationDetails();
        // we drop everything, except first entry
        $publication = array_shift($publication);
        if (is_object($publication)) {
            if ($date = $publication->getDate()) {
                $params['rft.date'] = preg_replace('/[^0-9]/', '', $date);
            }
            if ($place = $publication->getPlace()) {
                $params['rft.place'] = $place;
            }
        }
        $params['rft.volume'] = $this->getVolume();




        $publishers = $this->getPublishers();
        if (count($publishers) > 0) {
            $params['rft.pub'] = $publishers[0];
        }

        $params['rft.edition'] = $this->getEdition();
        $params['rft.isbn'] = (string) $this->getCleanISBN();
        return array_filter($params);
    }

    /**
     * Get OpenURL parameters for an article.
     *
     *
     *
     * @return array
     */
    protected function getArticleOpenUrlParams()
    {
        $params = $this->getDefaultOpenUrlParams();
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
        $params['rft.genre'] = $this->isContainerMonography() ? 'bookitem' : 'article';
        $params['rft.issn'] = (string) $this->getCleanISSN();
        // an article may have also an ISBN:
        $params['rft.isbn'] = (string) $this->getCleanISBN();
        $params['rft.volume'] = $this->getContainerVolume();
        $params['rft.issue'] = $this->getContainerIssue();
        $params['rft.date'] = $this->getContainerYear();
        if (strpos($this->getContainerPages(), '-') !== FALSE) {
            $params['rft.pages'] = $this->getContainerPages();
        } else {
            $params['rft.spage'] = $this->getContainerPages();
        }
        // unset default title -- we only want jtitle/atitle here:
        unset($params['rft.title']);
        $params['rft.jtitle'] = $this->getContainerTitle();
        $params['rft.atitle'] = $this->getTitle();
        $authors = $this->getAllAuthorsShort();
        $params['rft.au'] = array_shift($authors);
        
        $params['rft.format'] = 'Article';
        $langs = $this->getLanguages();
        if (count($langs) > 0) {
            $params['rft.language'] = $langs[0];
        }
                    // Fallback: add dirty data from 773g to openurl
        if (empty($params['rft.pages']) && empty($params['rft.spage'])) {
            $params['rft.pages'] = $this->getContainerRaw();
        }
        return array_filter($params);
    }

    /**
     * Get OpenURL parameters for a journal.
     *
     * @return array
     */
    protected function getJournalOpenURLParams()
    {
        $places = $this->getPlacesOfPublication();
        $params = $this->getDefaultOpenUrlParams();
        
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
        $params['rft.issn'] = (string) $this->getCleanISSN();
        $params['rft.jtitle'] = $this->getTitle();
        $params['rft.genre'] = 'journal';
        $params['rft.place'] = array_shift($places);
        // zdbid is allowed in pid zone only - it is moved there
        // in OpenURL helper
        $params['pid'] = 'zdbid='.$this->getZdbId();

        return array_filter($params);
    }

    /**
     * Pulling isils from field 924
     * @return array
     */
    public function getIsils()
    {
        return $this->getInstitutions();
    }

    /**
     * For Journals: Returns the holdings by date
     * @return array
     */
    public function getHoldingsDate()
    {
        $data = $this->getFieldArray('924', ['b', 'q'], true);
        $holdings = [];
        try {
            foreach ($data as $line) {

                $tmp = explode(' ', $line);
                $set = [];
                for ($i = 1; $i < count($tmp); $i++) {
                    if (isset($tmp[$i])) {
                        $from = $tmp[$i];
                        $to = isset($tmp[$i + 1]) ? $tmp[$i + 1] : null;
                        $set[] = [
                            'from' => isset($from) ? (int) $from : null,
                            'to' => isset($to) ? (int) $to : null,
                        ];
                        $i++;
                    }
                }
                $holdings[$tmp[0]] = $set;
            }
        } catch (\Exception $ex) {
            return null;
        }
        return $holdings;
    }

    /**@deprecated use specialized record_type classes
     * Returns German library network shortcut.
     * @return string
     */
    public function getNetwork()
    {
        $raw = trim($this->getUniqueID());
        preg_match('/\((.*?)\)/', $raw, $matches);
        $isil = $matches[1];
        return $this->client->mapNetwork($isil);
    }

    /**
     * Returns either Isil or Library name
     * @return array
     * @throws \Bsz\Exception
     */
    public function getLibraries()
    {

        $libraries = $this->getFieldArray(924, ['b']);
        return $libraries;
    }

    /**
     * Return system requirements
     */
    public function getSystemDetails()
    {
        return $this->getFieldArray('538', ['a'], true);
    }

    /**
     * Used in ResultScroller Class. Does not work when string is interlending
     * @return string
     */
    public function getResourceSource()
    {
        $id = $this->getSourceIdentifier();
        return $id == 'Solr' ? 'VuFind' : $id;
    }

    /**
     * Returns an array of related items for multipart results, including
     * its own id
     * @return array
     */
    public function getIdsRelated()
    {
        $ids = [];
        $f773 = $this->getFieldArray(773, ['w'], false);
        foreach ($f773 as $w) {
            $ids[] = $w;
        }
        $ids[] = $this->getUniqueId();
        return array_unique($ids);
    }

    public function getRelatedEditions()
    {
        $related = [];
        # 775 is RAK and 776 RDA *confused*
        $f77x = $this->getMarcRecord()->getFields('77[56]', true);
        foreach ($f77x as $field) {
            $tmp = [];
            $subfields = $field->getSubfields();
            foreach ($subfields as $subfield) {
                switch ($subfield->getCode()) {
                    case 'i': $label = 'description';
                        break;
                    case 't': $label = 'title';
                        break;
                    case 'w' : $label = 'id';
                        break;
                    case 'a' : $label = 'author';
                        break;
                    default: $label = 'unknown_field';
                }
                if (!array_key_exists($label, $tmp)) {
                    $tmp[$label] = $subfield->getData();
                }
                if (!array_key_exists('description', $tmp)) {
                       $tmp['description'] = 'Parallelausgabe';
                }
            }
            // exclude DNB records
            if (isset($tmp['id']) && strpos($tmp['id'], 'DE-600') === FALSE) {
                $related[] = $tmp;
            }

        }
        return $related;
    }

    /**
     * Returns Volume number
     * @return String
     */
    public function getVolume()
    {
        $fields = [
            245 => ['n'],
            490 => ['v']
        ];
        $volumes = $this->getFieldsArray($fields);
        return array_shift($volumes);
    }

    /**
     * return EAN Code
     * @return string
     */
    public function getEAN()
    {
        $ean = $this->getFieldArray("024", ['a']);
        return array_shift($ean);
    }

    /**
     * Returns unique publication details
     * @return array
     */
    public function getPublicationDetails()
    {
        $details = parent::getPublicationDetails();
        return $details;
    }

    /**
     * is this Record a FIS record
     * @return boolean
     */
    public function isFisBildung()
    {
        return false;
    }

    /**
     * Is this a DLR-Koha record
     * @return boolean
     */
    public function isDlrKoha()
    {
        return false;
    }

    /**
     * For rticles: get container title
     * @return type
     */
    public function getContainerTitle()
    {
        $fields = [
            773 => ['a', 't'], //SWB, GBV
            490 => ['v'], // BVB
            772 => ['t'], // HEBIS,
            780 => ['t']
        ];
        $array = $this->getFieldsArray($fields);
        $title = array_shift($array);
        return str_replace('In: ', '', $title);
    }

    /**
     * Get the Container issue from different fields
     * @return string
     */
    public function getContainerIssue()
    {
        $fields = [
            936 => ['e'],
            953 => ['e'],
            773 => ['g']
        ];
        $issue = $this->getFieldsArray($fields);
        if (count($issue) > 0 && !empty($issue[0])) {
            $string = array_shift($issue);
            return str_replace(' ', '/', $string);
        }
        return '';
    }

    /**
     * Get container pages from different fields
     * @return string
     */
    public function getContainerPages()
    {
        $fields = [
            936 => ['h'],
            953 => ['h'],
            773 => ['t'] // bad data, mixed into title field
        ];
        $pages = $this->getFieldsArray($fields);
        foreach ($pages as $k => $page) {
            preg_match('/\d+ *-? *\d*/', $page, $tmp);
            if (isset($tmp[0]) && $tmp[0] != '-') {
                $pages[$k] = $tmp[0];
            } else {
                unset($pages[$k]);
            }
        }
        return array_shift($pages);
    }

    /**
     * get container year from different fields
     * @return string
     */
    public function getContainerYear()
    {
        $fields = [
            260 => ['c'],
            936 => ['j'],
            363 => ['i'],
            773 => ['t', 'd']
        ];

        $years = $this->getFieldsArray($fields);
        foreach ($years as $k => $year) {
            preg_match('/\d{4}/', $year, $tmp);
            if (isset($tmp[0])) {
                $years[$k] = $tmp[0];
            } else {
                unset($years[$k]);
            }
        }        
        return array_shift($years);
    }

    /**
     * This method returns dirty data, don't use it except for ILL!
     */
    public function getContainerRaw() {
        $f773g = $this->getFieldArray(773, ['g']);
        return array_shift($f773g);
    }

    /**
     * get local Urls from 924|k and the correspondig linklabel 924|l
     *
     * - $924 is repeatable
     * - |k is repeatable, |l aswell
     * - we can have more than one isil ?is this true? maybe allways the first isil
     * - different Urls from one instition may have different issues (is this true?)
     *
     * @return array
     */
    public function getLocalUrls()
    {
        $localUrls = [];
        $field = '924'; // Bestandangaben, SWB only
        // take only the first ISIL from config
        $isilsconfig = $this->client->getIsils();
        $isilcurrent = '';
        $addedurls = [];

        $holdings = $this->getLocalHoldings();

        foreach ($holdings as $holding) {
            $isilcurrent = isset($holding['b']) ? $holding['b'] : null;
            $isils = $this->client->getIsils();
            // we assume the first isil in config.ini is the most important one
            $firstIsil = array_shift($isils);

            $address = isset($holding['k']) ? $holding['k'] : null;
            $label = isset($holding['l']) ? $holding['l'] : null;
            // Is there a label?  If not, just use the URL itself.
            if (empty($label)) {
                $label = $address;
            }
            // Prevent adding the same url multiple times
            if (!in_array($address, $addedurls) && !empty($address)
                    && $firstIsil == $isilcurrent
            ) {
                $localUrls[] = ['isil' => $isilcurrent, 'url' => $address, 'label' => $label];
            }
            $addedurls[] = $address;
        }
        return $localUrls;
    }

    /**
     * Has this record holdings in field 924
     *
     * @return boolean
     */
    public function hasLocalHoldings()
    {
        $holdings = $this->getLocalHoldings();
        return count($holdings) > 0;

    }

    /**
     * This method supports wildcard operators in ISILs.
     * @return array
     */
    public function getLocalHoldings()
    {
        $holdings = [];
        $f924 = $this->getField924(false,true);
        $isils = $this->client->getIsilAvailability();

        // Building a regex pattern
        foreach ($isils as $k => $isil) {
            $isils[$k] = preg_quote($isil, '/');
        }
        $pattern = implode('|', $isils);
        $pattern = '/'.str_replace('\*', '.*', $pattern).'/' ;

        foreach ($f924 as $fields) {
            if (isset($fields['b']) && preg_match($pattern, $fields['b'])) {
                $holdings[] = $fields;
            }
        }

        return $holdings;
    }

    /**
     * Get ZDB ID if available
     *
     * @return string
     */
    public function getZdbId()
    {
        $zdb = '';
        $consortial = $this->getConsortialIDs();
        foreach ($consortial as $id) {
            if (($pos = strpos($id, 'ZDB')) !== FALSE) {
                $zdb = substr($id, $pos+3);
            }
        }
        // Pull ZDB ID out of recurring field 016
        foreach ($this->getMarcRecord()->getFields('016') as $field) {
            $isil = $data = '';
            foreach ($field->getSubfields() as $subfield) {
                if ($subfield->getCode() == 'a') {
                    $data = $subfield ->getData();
                } elseif($subfield->getCode() == '2') {
                    $isil = $subfield->getData();
                }
            }
            if ($isil == 'DE-600') {
                $zdb = $data;
            }
        }
        return $zdb;
    }
    /**
     * is this a Journal, implies it's a serial
     *
     * @return boolean
     */
    public function isJournal()
    {
        $f008 = null;
        $f008_21 = '';
        $f008 = $this->getMarcRecord()->getFields("008", false);

        foreach ($f008 as $field) {
            $data = strtoupper($field->getData());
            if (strlen($data) >= 21) {
                $f008_21 = $data{21};
            }
        }
        if ($this->isSerial() && $f008_21 == 'P') {
            return true;
        }
        return false;
    }

    /**
     * General serial items. More exact is:
     * isJournal(), isNewspaper() isMonographicSerial()
     * @return boolean
     */
    public function isSerial()
    {
        $leader = $this->getMarcRecord()->getLeader();
        $leader_7 = strtoupper($leader{7});
        if ($leader_7 === 'S') {
            return true;
        }
        return false;
    }

    /**
     * iIs this a Newspaper?
     *
     * @return boolean
     */
    public function isNewspaper()
    {
        $f008 = null;
        $f008_21 = '';
        $f008 = $this->getMarcRecord()->getFields("008", false);

        foreach ($f008 as $field) {
            $data = strtoupper($field->getData());
            if (strlen($data) >= 21) {
                $f008_21 = $data{21};
            }
        }
        if ($this->isSerial() && $f008_21 == 'N') {
            return true;
        }
        return false;
    }

    /**
     * Dedup Functions
     *
     * @return boolean
     */
    
    public function isSubRecord() 
    {
        return isset($this->fields['_isSubRecord']) ? $this->fields['_isSubRecord'] : false;
    }
    
    public function getSubRecords() 
    {
        return isset($this->fields['_subRecords']) ? $this->fields['_subRecords'] : null;
    }
    
    public function hasSubRecords() 
    {
        if (null !== ($collection = $this->getSubRecords())) {
            return 0 < $collection->count();
        }
        return false;
    }    
    
        /**
     * Get Status/Holdings Information from the internally stored MARC Record
     * (support method used by the NoILS driver).
     *
     * @param array $field The MARC Field to retrieve
     * @param array $data  A keyed array of data to retrieve from subfields
     *
     * @return array
     */
    public function getFormattedMarcDetails($field, $data)
    {
        return parent::getFormattedMarcDetails($field, $data);
    }


}
