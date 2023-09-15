<?php

/*
 * Copyright 2020 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
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
 *
 */
namespace Bsz\RecordDriver;

use Exception;
use File_MARC;
use File_MARC_Exception;
use File_MARCBASE;
use File_MARCXML;
use phpDocumentor\Reflection\Types\Boolean;
use VuFind\RecordDriver\IlsAwareTrait;
use VuFind\RecordDriver\MarcAdvancedTrait;
use VuFind\RecordDriver\MarcReaderTrait;
use VuFind\Search\SearchRunner;
use VuFindCode\ISBN;

/**
 * This is the base BSZ SolrMarc class
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrMarc extends \VuFind\RecordDriver\SolrMarc
{
    use MarcFormatTrait;
    use IlsAwareTrait;
    use MarcReaderTrait;
    use MarcAdvancedTrait;
    use HelperTrait;

    protected $formats;
    protected $runner;
    protected $container = [];

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
                            $currentLine .= $data . ' ';
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
     * is this item a collection
     * @return bool
     * @deprecated the differences between "parts" and "collections" is not clear
     * @throws File_MARC_Exception
     */
    public function isCollection() : bool
    {
        $leader = $this->getMarcRecord()->getLeader();
        $leader07 = $leader{7};
        $leader19 = $leader{19};

        if ($leader07 == 'm' && $leader19 == 'a') {
            return true;
        }
        return false;
    }

    /**
     * is this item part of a collection?
     *
     * @deprecated the differences between "parts" and "collections" is not clear
     * @return boolean
     */
    public function isPart() : bool
    {
        $leader = $this->getMarcRecord()->getLeader();
        $leader07 = $leader{7};
        $leader19 = $leader{19};

        if ($leader07 == 'm' && preg_match('/b|c/', $leader19)) {
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
    public function attachSearchRunner(SearchRunner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * Get an array of all the formats associated with the record. The array is
     *  already simplified and unified.
     *
     * @return array
     */

    public function getFormats() : array
    {
        if ($this->formats === null && isset($this->formatConfig)) {
            $formats = [];
            if ($this->isElectronic()) {
                $formats[] = 'Online';
            }
            $formats[] = $this->getFormatMarc();
            $formats[] = $this->getFormatRda();
            $this->formats = $this->simplifyFormats($formats);
        }
        return $this->formats ?? [];
    }

    /**
     * Get Content of 924 as array: isil => array of subfields
     * @return array
     *
     */
    public function getField924()
    {
        $f924 = $this->getMarcRecord()->getFields('924');

        // map subfield codes to human-readable descriptions
        $mappings = [
            'a' => 'local_idn',         'b' => 'isil',      'c' => 'region',
            'd' => 'ill_indicator', 'g' => 'call_number', 'k' => 'url',
            'l' => 'url_label', 'z' => 'issue'
        ];

        $result = [];

        foreach ($f924 as $field) {
            $subfields = $field->getSubfields();
            $arrsub = [];

            foreach ($subfields as $subfield) {
                $code = $subfield->getCode();
                $data = $subfield->getData();

                if (array_key_exists($code, $mappings)) {
                    $mapping = $mappings[$code];
                    if (array_key_exists($mapping, $arrsub)) {
                        // recurring subfields are temporarily concatenated to a string
                        $data = $arrsub[$mapping] . ' | ' . $data;
                    }
                    $arrsub[$mapping] = $data;
                }
            }

            // fix missing isil fields to avoid upcoming problems
            if (!isset($arrsub['isil'])) {
                $arrsub['isil'] = '';
            }
            // handle recurring subfields - convert them to array
            foreach ($arrsub as $k => $sub) {
                if (strpos($sub, ' | ')) {
                    $split = explode(' | ', $sub);
                    $arrsub[$k] = $split;
                }
            }
            $result[] = $arrsub;
        }
        return $result;
    }

    protected function code2icon($code)
    {
        switch ($code) {
            case 'b': $icon = 'fa-copy'; break;
            case 'd': $icon = 'fa-times text-danger'; break;
            case 'e': $icon = 'fa-network-wired text-success'; break;
            default: $icon = 'fa-check text-success';
        }
        return $icon;
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
            $raw = $this->getFieldArray($no, (array)$subfield, true);
            if (count($raw) > 0 && !empty($raw[0])) {
                return $raw;
            }
        }
        return [];
    }

    public function getAllFieldsArray($fields)
    {
        $result = [];
        foreach ($fields as $no => $subfield) {
            $raw = $this->getFieldArray($no, (array)$subfield, false);
            $result = array_merge($result, $raw);
        }
        return array_unique($result);
    }

    /**
     * Get access to the raw File_MARC object.
     *
     * @return File_MARCBASE
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
                    $marc = new File_MARCXML($marc, File_MARCXML::SOURCE_STRING);
                } catch (Exception $ex) {
                    /**
                     * Replace asci control chars and & chars not followed bei amp;
                     */
                    $marc = preg_replace(['/#[0-9]*;/', '/&(?!amp;)/'], ['', '&amp;'], $backup);
                    $marc = new File_MARCXML($marc, File_MARCXML::SOURCE_STRING);
                    // Try again
                }
                error_reporting($errorReporting);
            } else {
                // When indexing over HTTP, SolrMarc may use entities instead of
                // certain control characters; we should normalize these:
                $marc = str_replace(
                    ['#29;', '#30;', '#31;'],
                    ["\x1D", "\x1E", "\x1F"],
                    $marc
                );
                $marc = new File_MARC($marc, File_MARC::SOURCE_STRING);
            }

            $this->lazyMarcRecord = $marc->next();
            if (!$this->lazyMarcRecord) {
                throw new File_MARC_Exception('Cannot Process MARC Record');
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
        } elseif ($this->isSerial()) {
            // Newspapers, Journals
            return 'Journal';
        } elseif ($this->isElectronicBook() || in_array('Book', $formats)) {
            return 'Book';
        } elseif (count($formats) > 0) {
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
    public function getPPN(): string
    {
        $m001 = $this->getMarcRecord()->getField('001');
        return is_object($m001) ? $m001->getData() : '';
    }

    /**
     * Returns ISBN as string. ISBN-13 preferred
     *
     * @return mixed
     */
    public function getCleanISBN(): string
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
            $issn = substr($issn, 0, 4) . '-' . substr($issn, 4, 4);
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
    public function getPublishers(): array
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
    public function getTitle(): string
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
        $shortTitle = $this->getFirstFieldValue('245', ['a'], false);

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
    public function getSubtitle(): string
    {
        $subTitle = $this->getFirstFieldValue('245', ['b'], false);

        // Sortierzeichen weg
        if (strpos($subTitle, '@') !== false) {
            $occurrence = strpos($subTitle, '@');
            $subTitle = substr_replace($subTitle, '', $occurrence, 1);
        }

        return $this->cleanString($subTitle);
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
     * Get an array of publication detail lines combining information from
     * getPublicationDates(), getPublishers() and getPlacesOfPublication().
     *
     * @return array
     */
    public function getPublicationDetails()
    {
        $places = $this->getPlacesOfPublication();
        $names = $this->getPublishers();
        $dates = $this->getHumanReadablePublicationDates();

        // special case one publisher with multiple places
        if (count($names) == 1 && count($dates) == 1 && count($places) > 1) {
            $places = [implode(', ', $places)];
        }

        $i = 0;
        $retval = [];
        while (isset($places[$i]) || isset($names[$i]) || isset($dates[$i])) {
            // Build objects to represent each set of data; these will
            // transform seamlessly into strings in the view layer.
            $retval[] = new Response\PublicationDetails(
                $places[$i] ?? '',
                $names[$i] ?? '',
                $dates[$i] ?? ''
            );
            $i++;
        }
        return $retval;
    }

    /**
     * Get the two char code in 007
     *
     * @return string
     */
    private function get007()
    {
        $f007 = null;
        $f007_0 = $f007_1 = '';
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
        return $f007_0 . $f007_1;
    }
}
