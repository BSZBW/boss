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

use Bsz\Exception;
use Bsz\Tools\GndHelperTrait;
use VuFind\RecordDriver\Feature\IlsAwareTrait;
use VuFind\RecordDriver\Feature\MarcAdvancedTrait;
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
    use AdvancedMarcReaderTrait;
    use IlsAwareTrait, MarcAdvancedTrait {
        MarcAdvancedTrait::getURLs insteadof IlsAwareTrait;
    }
    use HelperTrait;
    use GndHelperTrait;

    protected $formats;
    protected $runner;
    protected $container = [];

    protected $lazyLocalUrls;

    /**
     * is this item a collection
     * @return bool
     * @deprecated the differences between "parts" and "collections" is not clear
     */
    public function isCollection() : bool
    {
        $leader07 = $this->getLeader(7);
        $leader19 = $this->getLeader(19);

        if ($leader07 == 'm' && $leader19 == 'a') {
            return true;
        }
        return false;
    }

    /**
     * is this item part of a collection?
     *
     * @deprecated the differences between "parts" and "collections" is not clear
     * @return bool
     */
    public function isPart() : bool
    {
        $leader07 = $this->getLeader(7);
        $leader19 = $this->getLeader(19);

        if ($leader07 == 'm' && preg_match('/[bc]/', $leader19)) {
            return true;
        }
        return false;
    }

    /**
     * Attach a Search Results Plugin Manager connection and related logic to
     * the driver
     *
     * @param \VuFind\Search\SearchRunner $runner
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
     * @throws Exception
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
        $f924 = $this->getFields('924');

        // map subfield codes to human-readable descriptions
        $mappings = [
            'a' => 'local_idn',         'b' => 'isil',      'c' => 'region',
            'd' => 'ill_indicator', 'g' => 'call_number', 'k' => 'url',
            'l' => 'url_label', 'z' => 'issue'
        ];

        $result = [];

        foreach ($f924 as $field) {
            $arrsub = [];

            foreach ($field['subfields'] ?? [] as $subfield) {
                $code = $subfield['code'];
                $data = $subfield['data'];

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
            if(isset($arrsub['call_number']) && $arrsub['call_number'] == '--%%--') {
                unset($arrsub['call_number']);
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

    //TODO: Deprecated?
//    /**
//     * Get access to the raw File_MARC object.
//     *
//     * @return File_MARCBASE
//     */
//    public function getMarcRecord()
//    {
//        if (null === $this->lazyMarcRecord) {
//            $marc = trim($this->fields['fullrecord']);
//            $backup = $marc;
//
//            // check if we are dealing with MARCXML
//            if (substr($marc, 0, 1) == '<') {
//                $errorReporting = error_reporting();
//                error_reporting(E_ERROR);
//                try {
////                    error_reporting(0);
//                    $marc = new File_MARCXML($marc, File_MARCXML::SOURCE_STRING);
//                } catch (Exception $ex) {
//                    /**
//                     * Replace asci control chars and & chars not followed bei amp;
//                     */
//                    $marc = preg_replace(['/#[0-9]*;/', '/&(?!amp;)/'], ['', '&amp;'], $backup);
//                    $marc = new File_MARCXML($marc, File_MARCXML::SOURCE_STRING);
//                    // Try again
//                }
//                error_reporting($errorReporting);
//            } else {
//                // When indexing over HTTP, SolrMarc may use entities instead of
//                // certain control characters; we should normalize these:
//                $marc = str_replace(
//                    ['#29;', '#30;', '#31;'],
//                    ["\x1D", "\x1E", "\x1F"],
//                    $marc
//                );
//                $marc = new File_MARC($marc, File_MARC::SOURCE_STRING);
//            }
//
//            $this->lazyMarcRecord = $marc->next();
//            if (!$this->lazyMarcRecord) {
//                throw new File_MARC_Exception('Cannot Process MARC Record');
//            }
//        }
//
//        return $this->lazyMarcRecord;
//    }

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
        $f001 = $this->getField('001');
        return is_string($f001) ? $f001 : '';
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
        //TODO: Remove
//        $languages = [];
//        $marc = $this->getMarcReader();
//        $fields = $marc->getFields('041');
//        foreach ($fields as $field) {
//            foreach ($field->getSubFields('a') as $sf) {
//                $languages[] = $sf->getData();
//            }
//        }
//        return $languages;
        return $this->getFieldArray('041', 'a', false);
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
        $shortTitle = $this->getFirstFieldValue('245', ['a']);

        // Sortierzeichen weg
        if (str_contains($shortTitle, '@')) {
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
        $subTitle = $this->getFirstFieldValue('245', ['b']);

        // Sortierzeichen weg
        if (str_contains($subTitle, '@')) {
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
        $f007_0 = $f007_1 = '';
        $f007 = $this->getFields("007");
        foreach ($f007 as $field) {
            $data = is_string($field) ? strtoupper($field) : '';
            if (strlen($data) > 0) {
                $f007_0 = $data[0];
            }
            if (strlen($data) > 1) {
                $f007_1 = $data[1];
            }
        }
        return $f007_0 . $f007_1;
    }

    public function getProvenances(string $isils): array
    {
        if($isils == '*') {
            $isils = ['*'];
        }else {
            $isils = array_map('trim', explode(',', $isils));
        }

        $retVal = [];
        $f561 = $this->getFields('561');
        foreach ($f561 as $field) {
            $entry = [];

            $sf5 = $this->getSubfield($field, '5');
            if ($isils != ['*'] && !in_array($sf5, $isils)) {
                continue;
            }
            $sf3 = $this->getSubfield($field, '3');
            if (isset($sf3)) {
                $pos = strpos($sf3, 'Signatur');
                if ($pos !== false) {
                    $entry['signature'] = substr($sf3, $pos);
                }
            }

            $sfa = $this->getSubfield($field, 'a');
            if (isset($sfa)) {
                $data = explode(' / ', $sfa);

                $subData = explode(';', $data[0]);
                $splittedName = explode(':', $subData[0], 2);
                if (count($splittedName) > 1) {
                    $entry['header'] = $splittedName[0];
                    $entry['name'] = trim($splittedName[1]);
                } else {
                    $entry['name'] = $splittedName[0];
                }

                if (count($subData) > 1) {
                    $gnd = $this->gndIdFromLink($subData[1]);
                    if ($gnd) {
                        $entry['gnd'] = $gnd;
                    }
                }
                $entry['details'] = array_slice($data, 1);
            }

            if (!empty($entry)) {
                $retVal[] = $entry;
            }
        }

        return $retVal;
    }

    protected function getHoldingIsilsFromField(string $fieldTag, string $subfieldCode): array
    {
        $isils = $this->mainConfig->getIsilAvailability();
        if (count($isils) == 0) {
            return [];
        }
        foreach ($isils as $k => $isil) {
            $isils[$k] = '^' . preg_quote($isil, '/') . '$';
        }
        $pattern = implode('|', $isils);
        $pattern = '/' . str_replace('\*', '.*', $pattern) . '/';

        $fields = $this->getFields($fieldTag);

        $retVal = [];
        foreach ($fields as $field) {
            if(!is_array($field)) {
                continue;
            }
            $sf = $this->getSubfield($field, $subfieldCode);
            if(preg_match($pattern, $sf)) {
                $retVal[] = $sf;
            }
        }
        return $retVal;
    }

    public function getLocalUrls()
    {
        return [];
    }

    public function retrieveLocalUrls()
    {
        if($this->lazyLocalUrls == null) {
            $this->lazyLocalUrls = $this->getLocalUrls();
        }
        return $this->lazyLocalUrls;
    }

    public function hasLocalUrls(): bool
    {
        $localUrls = $this->retrieveLocalUrls();
        return isset($localUrls[0]['url']);
    }

}
