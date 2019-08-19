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
class SolrGviMarc extends SolrMarc implements Definition
{
    use \VuFind\RecordDriver\IlsAwareTrait;
    use \VuFind\RecordDriver\MarcReaderTrait;
    use \VuFind\RecordDriver\MarcAdvancedTrait;    
    use SubrecordTrait;  
    use HelperTrait;
    use ContainerTrait;
    use MarcAuthorTrait;
    
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

                // Get all the chunks and collect them together:
                $subfields = $result->getSubfields();
                if ($subfields) {
                    foreach ($subfields as $subfield) {
                        // Numeric subfields are for control purposes and should not
                        // be displayed:
                        if (!is_numeric($subfield->getCode())
                                && ($subfield->getCode() == "a" || $subfield->getCode() == "x")) {
                            array_push($retval, $subfield->getData());
                        }
                    }
                }
            }
        }

        // Send back everything we collected:
        return array_unique($retval);
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
            '656', '689'];
        $headings = $this->getSubjectHeadings($fields);
        return $headings;
    }

    
    
    /**
     * Get an array with DFI classification
     * @returns array
     */
    public function getDFIClassification()
    {
        $classificationList = [];
        foreach ($this->getMarcRecord()->getFields('084') as $field) {
            $suba = $field->getSubField('a');
            $sub2 = $field->getSubfield('2');
            if ($suba && $sub2) {
                $sub2data = $field->getSubfield('2')->getData();
                if (strtolower($sub2data) == 'dfi') {
                    $classificationList[] = $suba->getData();
                }
            }
        }
        return array_unique($classificationList);
    }

    
    /**
     * Get an array with FIV classification
     * @returns array
     */
    public function getFIVClassification()
    {
        $classificationList = [];
        foreach ($this->getMarcRecord()->getFields('084') as $field) {
            $suba = $field->getSubField('a');
            $sub2 = $field->getSubfield('2');
            if ($suba && $sub2) {
                $sub2data = $field->getSubfield('2')->getData();
                if (strtolower($sub2data) == 'fiv') {
                    $classificationList[] = $suba->getData();
                }
            }
        }
        return array_unique($classificationList);
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
            if ($suba && $sub2) {
                $sub2data = $field->getSubfield('2')->getData();
                if (strtolower($sub2data) == 'rvk') {
                    $title = [];
                    foreach ($field->getSubFields('k') as $item) {
                        $title[] = htmlentities($item->getData());
                    }
                    $notationList[$suba->getData()] = $title;
                }
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
    public function getISBNs() : array
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
    public function getISSNs() : array
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
     * Get a note about languages and text
     *
     * @return string
     */
    public function getNote()
    {
        return $this->getFirstFieldValue('546', ['a']);
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
    public function getURLs() : array
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

            if (($sf = $f->getSubField('3')) && strlen($sf->getData()) > 2) {
                $url['desc'] = $sf->getData();
            } elseif (($sf = $f->getSubField('y'))) {
                $url['desc'] = $sf->getData();                
            } elseif (($sf = $f->getSubField('n'))) {
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

    /**
     * Returns consortium
     * @return array
     * @throws \Bsz\Exception
     */
    public function getConsortium()
    {
        // determine network based on two different sources
        $consortium1 = $this->getFirstFieldValue(924, ['c']);
        $consortium1 = explode(' ', $consortium1);
        $consortium2 = $this->fields['consortium']; 
        $consortium = array_merge($consortium1, $consortium2);
        
        foreach ($consortium as $k => $con) {
            $mapped = $this->mainConfig->mapNetwork($con);
            if (!empty($mapped)) {
                $consortium[$k] = $mapped;

            }
        }
        $consortium_unique = array_unique($consortium);

        $string = implode(", ",$consortium_unique);
        return $string;        
    }
       
  

    /**
     * Get the main corporate author (if any) for the record.
     *
     * @return string
     */
    public function getCorporateAuthor()
    {
        // Try 110 first -- if none found, try 710 next.
        $corpAuthors = array_merge($this->getFieldArray('110', array('a', 'b', 'g', '9'), true), $this->getFieldArray('710', array('a', 'b', 'g'), true));
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
            $conf = $this->mainConfig->get('Index')->get('group.field');
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

    /**
     * Get an array of information about record holdings, obtained in real-time
     * from the ILS.
     *
     * @return array
     */
    public function getRealTimeHoldings()
    {
        if ($this->mainConfig->isIsilSession() && !$this->mainConfig->hasIsilSession()) {
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
        if ($this->mainConfig->isIsilSession() && !$this->mainConfig->hasIsilSession()) {
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
        $publishers = $this->getPublishers();

        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
        $params['rft.issn'] = (string) $this->getCleanISSN();
        $params['rft.jtitle'] = $this->getTitle();
        $params['rft.genre'] = 'journal';
        $params['rft.place'] = array_shift($places);
        $params['rft.pub'] = array_shift($publishers);
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
        $f773 = $this->getFieldArray(773, ['w']);
        foreach ($f773 as $subfields) {
            $ids = explode(' ', $subfields);
            foreach ($ids as $id) {
                // match all PPNs except old SWB PPNs and ZDB-IDs (with dash)
                if (preg_match('/^((?!DE-576|DE-600.*-).)*$/', $id )  ) {
                    $ids[] = $id;
                }
            }
            
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
            245 => ['n', 'p'],
            490 => ['v']
        ];
        $volumes = preg_replace("/\/$/", "", $this->getFieldsArray($fields));
        return array_shift($volumes);
    }
    
        /**
     * Returns Volume number
     * @return String
     */
    public function getVolumeNumber()
    {
        $fields = [
            245 => ['n'],
            490 => ['v']
          ];
        $volumes = preg_replace("/[\/,]$/", "", $this->getFieldsArray($fields));
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
     * Is this a DLR-Koha record
     * @return boolean
     */
    public function isDlrKoha()
    {
        return false;
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
        $isilsconfig = $this->mainConfig->getIsils();
        $isilcurrent = '';
        $addedurls = [];

        $holdings = $this->getLocalHoldings();

        foreach ($holdings as $holding) {
            $isilcurrent = isset($holding['b']) ? $holding['b'] : null;
            $isils = $this->mainConfig->getIsils();
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
        $isils = $this->mainConfig->getIsilAvailability();

        // Building a regex pattern
        foreach ($isils as $k => $isil) {
            $isils[$k] = preg_quote($isil, '/');
        }
        $pattern = implode('|', $isils);
        $pattern = '/^'.str_replace('\*', '.*', $pattern).'$/' ;

        foreach ($f924 as $fields) {
            if (isset($fields['b']) && preg_match($pattern, $fields['b'])) {
                $holdings[] = $fields;
            }
        }

        return $holdings;
    }

    /**
     *  Scale of a map
     */
    public function getScale() {
        $scale = $this->getFieldArray("255", ['a']);
        if (empty($scale)) {
            $scale = $this->getFieldArray("034", ['b']);
        }
        return array_shift($scale);
    }
    
    /**
     * Get ZDB ID if available
     *
     * @return string
     */
    public function getZdbId()
    {
        $zdb = '';
        $substr = '';
        $matches = [];
        $consortial = $this->getConsortialIDs();
        foreach ($consortial as $id) {
            $substr = preg_match('/\(DE-\d{3}\)ZDB(.*)/', $id, $matches);
            if (!empty($matches) && $matches[1] !== '') {
                $zdb = $matches[1];
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
        $parent = parent::getFormattedMarcDetails($field, $data);
        $return = [];
        foreach ($parent as $k => $item) {
            $ill_status = '';
            switch ($item['availability']) {
                case 'a': $ill_status = 'ILL::status_a';
                     break;
                case 'b': $ill_status = 'ILL::status_b';
                     break;
                case 'c': $ill_status = 'ILL::status_c';
                     break;
                case 'd': $ill_status = 'ILL::status_d';
                     break;
                case 'e': $ill_status = 'ILL::status_e';
                     break;
                case 'n':
                case 'N':
                     $ill_status = 'ILL::status_N';
                     break;
                case 'l':
                case 'L':                     
                     $ill_status = 'ILL::status_L';
                     break;                 
                default: $ill_status = 'ILL::status_d';
            }
            $item['availability'] = $ill_status;
            $return[] = $item;

        }
        return $return;
    }

}
