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

use Bsz\FormatMapper,
    VuFindCode\ISBN;
    

/**
 * This is the base BSZ SolrMarc class 
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrMarc extends \VuFind\RecordDriver\SolrMarc
{
    
    use \VuFind\RecordDriver\IlsAwareTrait;
    use \VuFind\RecordDriver\MarcReaderTrait;
    use \VuFind\RecordDriver\MarcAdvancedTrait;

    protected $mapper;
    protected $formats;
    protected $runner;
    protected $container = [];
    
    /**
     * 
     * @param FormatMapper $mapper
     * @param \Bsz\Config\Client $mainConfig
     * @param type $recordConfig
     * @param type $searchSettings
     */

    public function __construct(FormatMapper $mapper, 
            \Bsz\Config\Client $mainConfig = null, 
            $recordConfig = null,
            $searchSettings = null)
    {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);
        $this->mapper = $mapper;
    }

    /**
     * Return an array of non-empty subfield values found in the provided MARC
     * field.  If $concat is true, the array will contain either zero or one
     * entries (empty array if no subfields found, subfield values concatenated
     * together in specified order if found).  If concat is false, the array
     * will contain a separate entry for each subfield value found.
     *
     * @param object $currentField Result from File_MARC::getFields.
     * @param array  $subfields    The MARC subfield codes to read
     * @param bool   $concat       Should we concatenate subfields?
     *
     * @return array
     */
    protected function getSubfieldArray($currentField, $subfields, $concat = true, $separator = ' ')
    {
        // Start building a line of text for the current field
        $matches = [];
        $currentLine = '';

        // Loop through all subfields, collecting results that match the whitelist;
        // note that it is important to retain the original MARC order here!
        $allSubfields = $currentField->getSubfields();
        if (count($allSubfields) > 0) {
            foreach ($allSubfields as $currentSubfield) {
                if (in_array($currentSubfield->getCode(), $subfields)) {
                    // Grab the current subfield value and act on it if it is
                    // non-empty:
                    $data = trim($currentSubfield->getData());
                    if (!empty($data)) {
                        // Are we concatenating fields or storing them separately?
                        if ($concat) {
                            $currentLine .= $data . static::DELIMITER;
                        } else {
                            $matches[] = $data;
                        }
                    }
                }
            }
        }

        // If we're in concat mode and found data, it will be in $currentLine and
        // must be moved into the matches array.  If we're not in concat mode,
        // $currentLine will always be empty and this code will be ignored.
        if (!empty($currentLine)) {
            $matches[] = trim($currentLine);
        }

        // Send back our result array:
        return $matches;
    }

    /**
     * Get multipart level from leader 19
     * @return boolean|string
     */
    public function getMultipartLevel()
    {
               $leader = $this->getMarcRecord()->getLeader();
        $multipartLevel = strtoupper($leader{19});   
        
        switch ($multipartLevel) {
            case 'A':
                return static::MULTIPART_COLLECTION;
            //difference between B and C is if they have independend titles
            case 'B':
                return static::NO_MULTIPART;
            case 'C':
                return static::MULTIPART_PART;
            default: 
                return static::NO_MULTIPART;
        }
    }

    /**
     * Get bibliographic level from leader 7
     * @return string
     */
    public function getBibliographicLevel()
    {

        $leader = $this->getMarcRecord()->getLeader();
        $bibliographicLevel = strtoupper($leader{7});
        switch ($bibliographicLevel) {
            case 'A': // Monographic component part
                return static::BIBLIO_MONO_COMPONENT;
            //difference between B and C is if they have independend titles
            case 'B': // Serial component part
                return static::BIBLIO_SERIAL_COMPONENT;
            case 'C': // Collection
                return static::BIBLIO_COLLECTION;
            case 'D': //Subunit
                return static::BIBLIO_SUBUNIT;
            case 'I': //Integration resource
                return static::BIBLIO_INTEGRATED;
            case 'M': //Monograph/Item
                return static::BIBLIO_MONOGRAPH;
            case 'S': //Serial
                return static::BIBLIO_SERIAL;
        }
    }

    /**
     * is this item a collection
     * @return boolean
     */
    public function isCollection()
    {
        $collection = [
            static::MULTIPART_COLLECTION,
            static::BIBLIO_MONO_COMPONENT,
            static::BIBLIO_SERIAL_COMPONENT,
            static::BIBLIO_COLLECTION,
            static::BIBLIO_SUBUNIT,
            static::BIBLIO_INTEGRATED,
        ];
        if (in_array($this->getBibliographicLevel(), $collection) ||
                in_array($this->getMultipartLevel(), $collection)) {
            return true;
        }
        return false;
    }

    /**
     * is this item part of a collection?
     * @return boolean
     */
    public function isPart()
    {
        
        $part = [
            static::MULTIPART_PART,
            static::BIBLIO_SERIAL,
            static::BIBLIO_MONO_COMPONENT,
                
        ];
        $biblio = $this->getBibliographicLevel();
        $multi = $this->getMultipartLevel();
        
        
        if (in_array($biblio, $part) ||
                in_array($multi, $part)) {
            return true;            
        }
        return false;
    }

    /**
     * Attach a Search Results Plugin Manager connection and related logic to
     * the driver
     *
     * @param \VuFind\SearchRunner $runner
     * @return void
     */
    public function attachSearchRunner(\VuFind\Search\SearchRunner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * Get an array of all the formats associated with the record.
     *
     * @return array
     */
    public function getFormats()
    {
        if ($this->formats === null) {
            $formats = [];
            $f007 = $f008 = $leader = null;
            $f007_0 = $f007_1 = $f008_21 = $leader_6 = $leader_7 = '';

            //field 007 - physical description
            $f007 = $this->getMarcRecord()->getFields("007", false);
            foreach ($f007 as $field) {
                $data = strtoupper($field->getData());
                if (strlen($data) > 0) {
                    $f007_0 = $data{0};
                }
                if (strlen($data) > 1) {
                    $f007_1 = $data{1};
                }
            }
            $f008 = $this->getMarcRecord()->getFields("008", false);
            foreach ($f008 as $field) {
                $data = strtoupper($field->getData());
                if (strlen($data) > 21) {
                    $f008_21 = $data{21};
                }
            }

            $leader = $this->getMarcRecord()->getLeader();
            $leader_6 = $leader{6};
            $leader_7 = $leader{7};

            $formats[] = $this->mapper->marc21007($f007_0, $f007_1);
            $formats[] = $this->mapper->marc21leader7($leader_7, $f007_0, $f008_21);
            if ($this->isCollection() && !$this->isArticle()) {
                $formats[] = 'Compilation';
            } 

            $this->formats = array_filter($formats);
        }
        return $this->formats;
    }

    /**
     * Nach der Dokumentation des Fernleihportals
     * 
     * @return boolean
     */
    public function isArticle()
    {
        $leader = $this->getMarcRecord()->getLeader();
        $leader_7 = strtoupper($leader{7});
        // A = Aufsätze aus Monographien
        // B = Aufsätze aus Zeitschriften (wird aber wohl nicht genutzt))
        if ($leader_7 === 'A' || $leader_7 === 'B') {
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
     * Is this a book serie? 
     * @return boolean
     */
    public function isMonographicSerial() 
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
        if ($this->isSerial() && $f008_21 == 'M') {
            return true;
        }
        return false;
    }

    /**
     * Ist der Titel ein EBook? 
     * Wertet die Felder 007/00, 007/01 und Leader 7 aus
     * @return boolean
     */
    public function isEBook()
    {
        $f007 = $leader = null;
        $f007_0 = $f007_1 = $leader_7 = '';
        $f007 = $this->getMarcRecord()->getFields("007", false);
        foreach ($f007 as $field) {
            $data = strtoupper($field->getData());
            if (strlen($data) > 0) {
                $f007_0 = $data{0};
            }
            if (strlen($data) > 1) {
                $f007_1 = $data{1};
            }
        }
        $leader = $this->getMarcRecord()->getLeader();
        $leader_7 = strtoupper($leader{7});
        if ($leader_7 == 'M') {
            if ($f007_0 == 'C' && $f007_1 == 'R') {
                return true;
            }
        }
        return false;
    }
    /**
     * Ist der Titel ein Buch, das schließt auch eBooks mit ein!
     * Wertet den Leader aus
     * @return boolean
     */
    public function isBook()
    {
        $leader = $this->getMarcRecord()->getLeader();
        $leader_7 = strtoupper($leader{7});
        if ($leader_7 == 'M') {
            return true;
        }
        return false;
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
     * Determine  if a record is freely available. 
     * Indicator 2 references to the record itself. 
     * 
     * @return boolean
     */
    public function isFree()
    {
        $f856 = $this->getMarcRecord()->getFields(856);
        foreach ($f856 as $field) {
            
            $z = $field->getSubfield('z');
            if (is_string($z) && strpos(strtolower($z), 'kostenfrei') !== FALSE && $field->getIndicator(2) == 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get Content of 924 as array: isil => array of subfields
     * 
     * @param boolean $isilAsKey          uses ISILs as array keys - be carefull, 
     * information is dropped
     * @param boolean $recurringSubfields allow recurring subfields
     * 
     * @return array
     * 
     */
    public function getField924($isilAsKey = true, $recurringSubfields = false)
    {
        $f924 = $this->getMarcRecord()->getFields('924');
        $result = [];
        foreach ($f924 as $field) {
            $subfields = $field->getSubfields();
            $tmpSubfields = [];
            $isil = null;
            foreach ($subfields as $subfield) {
                if ($subfield->getCode() == 'b') {
                    $isil = $subfield->getData();
                        $tmpSubfields[$subfield->getCode()] = $isil;                        
                } elseif ($subfield->getCode() == 'd') {
                    $ill_status = '';
                    switch ($subfield->getData()) {
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
                        case 'L': $ill_status = 'ILL::status_L';
                            break;                 
                        default: $ill_status = 'ILL::status_d';
                    }
                    $tmpSubfields['d'] = $subfield->getData();
                    $tmpSubfields['ILL::status'] = $ill_status;
                } elseif (!isset($tmpSubfields[$subfield->getCode()])) {
                    // without $recurringSubfields, only the first occurence is 
                    // included
                    $tmpSubfields[$subfield->getCode()] = $subfield->getData();                        
                } elseif ($recurringSubfields) {
                    // with §recurringSubfields, all occurences are put together
                    $tmpSubfields[$subfield->getCode()] .= ' | '.$subfield->getData();                       
                    
                }
            }
            if (isset($isil) && $isilAsKey) {
                $result[$isil] = $tmpSubfields;
            } else {
                $result[] = $tmpSubfields;
            }
        }
        return $result;
    }

    /**
     * Get content from multiple fields, stops if one field returns something.
     * Order is important
     * @param array $fields
     * @return array
     */
    public function getFieldsArray($fields)
    {
        foreach ($fields as $no => $subfield) {
            $raw = $this->getFieldArray($no, (array) $subfield, true);
            if (count($raw) > 0 && !empty($raw[0])) {
                return $raw;
            }
        }
        return [];
    }

    /**
     * Get access to the raw File_MARC object.
     *
     * @return \File_MARCBASE
     */
    public function getMarcRecord()
    {
        if (null === $this->lazyMarcRecord) {
            $marc = trim($this->fields['fullrecord']);
            $backup = $marc;

            // check if we are dealing with MARCXML
            if (substr($marc, 0, 1) == '<') {
                $errorReporting = error_reporting();
                error_reporting(E_ERROR);
                try {
//                    error_reporting(0); 
                    $marc = new \File_MARCXML($marc, \File_MARCXML::SOURCE_STRING);
                } catch (\Exception $ex) {
                    /**
                     * Replace asci control chars and & chars not followed bei amp;
                     */
                    $marc = preg_replace(['/#[0-9]*;/', '/&(?!amp;)/'], ['', '&amp;'], $backup);
                    $marc = new \File_MARCXML($marc, \File_MARCXML::SOURCE_STRING);
                    // Try again                             
                }
                error_reporting($errorReporting);
            } else {
                // When indexing over HTTP, SolrMarc may use entities instead of
                // certain control characters; we should normalize these:
                $marc = str_replace(
                        ['#29;', '#30;', '#31;'], ["\x1D", "\x1E", "\x1F"], $marc
                );
                $marc = new \File_MARC($marc, \File_MARC::SOURCE_STRING);
            }

            $this->lazyMarcRecord = $marc->next();
            if (!$this->lazyMarcRecord) {
                throw new \File_MARC_Exception('Cannot Process MARC Record');
            }
        }

        return $this->lazyMarcRecord;
    }
    
    /**
     * parses Format to OpenURL genre
     * @return string
     */
    protected function getOpenURLFormat()
    {
        $formats = $this->getFormats();
        if ($this->isArticle()) {
            return 'Article';
        } else if ($this->isSerial()) {
            // Newspapers, Journals
            return 'Journal';
        } else if ($this->isEBook() || in_array('Book', $formats)) {
            return 'Book';
        } 
        else if (count($formats) > 0) {
            return array_shift($formats);
        }
        return 'Unknown';
    }
    
        /**
     * 
     * @param bool $overrideSupportsOpenUrl
     * @return string
     */
    public function getOpenUrl($overrideSupportsOpenUrl = false)
    {
        // stop here if this record does not support OpenURLs
        if (!$overrideSupportsOpenUrl && !$this->supportsOpenUrl()) {
            return false;
        }

        // Set up parameters based on the format of the record:
        $format = $this->getOpenUrlFormat();
        $method = "get{$format}OpenUrlParams";
        if (method_exists($this, $method)) {
            $params = $this->$method();
        } else {
            $params = $this->getUnknownFormatOpenUrlParams($format);
        }
        // Assemble the URL:
        return http_build_query($params);
    }
    
    /**
     * Get default OpenURL parameters.
     *
     * @return array
     */
    protected function getDefaultOpenUrlParams()
    {
        // Get a representative publication date:
        $pubDate = $this->getPublicationDates();
        $pubDate = empty($pubDate) ? '' : $pubDate[0];

        // Start an array of OpenURL parameters:
        return [
            'url_ver' => 'Z39.88-2004',
            'ctx_ver' => 'Z39.88-2004',
            'ctx_enc' => 'info:ofi/enc:UTF-8',
            'rfr_id' => 'info:sid/' . $this->getCoinsID() . ':generator',
            'rft.title' => $this->getTitle(),
            'rft.date' => $pubDate
        ];
    }
    
        /**
     * Get OpenURL parameters for an unknown format.
     *
     * @param string $format Name of format
     *
     * @return array
     */
    protected function getUnknownFormatOpenUrlParams($format = 'UnknownFormat')
    {
        $params = $this->getDefaultOpenUrlParams();
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:dc';
        $params['rft.creator'] = $this->getPrimaryAuthor();
        $publishers = $this->getPublishers();
        if (count($publishers) > 0) {
            $params['rft.pub'] = $publishers[0];
        }
        $params['rft.genre'] = $format;
        $langs = $this->getLanguages();
        if (count($langs) > 0) {
            $params['rft.language'] = $langs[0];
        }
        
        return $params;
    }
    
    /**
     * Get the call number associated with the record (empty string if none).
     *
     * @return string
     */
    public function getCallNumber() : string
    {
        return $this->getPPN();
    }
    
    /**
     * Get PPN of Record
     *
     * @return string
     */
    
    public function getPPN() : string
    {
        return $this->getMarcRecord()->getField('001')->getData();
    }
    
        /**
     * Returns ISBN as string. ISBN-13 preferred
     *
     * @return mixed
     */
    public function getCleanISBN() : string
    {

        // Get all the ISBNs and initialize the return value:
        $isbns = $this->getISBNs();
        $isbn10 = false;

        // Loop through the ISBNs:
        foreach ($isbns as $isbn) {
            // Strip off any unwanted notes:
            if ($pos = strpos($isbn, ' ')) {
                $isbn = substr($isbn, 0, $pos);
            }

            // If we find an ISBN-10, return it immediately; otherwise, if we find
            // an ISBN-13, save it if it is the first one encountered.
            $isbnObj = new ISBN($isbn);
            if ($isbn13 = $isbnObj->get13()) {
                return $isbn13;
            }
            if (!$isbn10) {
                $isbn10 = $isbnObj->get10();
            }
        }
        return $isbn10;
    }
    
    /**
     * Get just the base portion of the first listed ISSN (or false if no ISSNs).
     *
     * @return mixed
     */
    public function getCleanISSN() : string
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
    public function getLanguages() : array
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
     * Get the publishers of the record.
     *
     * @return array
     */
    public function getPublishers() : array 
    {
        $fields = [
            260 => 'b',
            264 => 'b',
        ];
        return $this->getFieldsArray($fields);
    }
    
     /**
     * Get the full title of the record.
     *
     * @return string
     */
    public function getTitle() : string
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
     * Get the short (pre-subtitle) title of the record.
     *
     * @return string
     */
    public function getShortTitle() : string
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
    public function getSubtitle() : string
    {
        $subTitle = $this->getFirstFieldValue('245', array('b'), false);

        // Sortierzeichen weg
        if (strpos($subTitle, '@') !== false) {
            $occurrence = strpos($subTitle, '@');
            $subTitle = substr_replace($subTitle, '', $occurrence, 1);
        }

        return $this->cleanString($subTitle);
    }
    
    
    
    

       

}
