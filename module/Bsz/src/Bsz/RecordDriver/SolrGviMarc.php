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
use VuFind\RecordDriver\Feature\IlsAwareTrait;
use VuFind\RecordDriver\Feature\MarcReaderTrait;

/**
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrGviMarc extends SolrMarc implements Constants
{
    use IlsAwareTrait;
    use MarcReaderTrait;
    use AdvancedMarcReaderTrait;
    use MarcAdvancedTraitBsz;
    use SubrecordTrait;
    use HelperTrait;
    use ContainerTrait;
    use MarcAuthorTrait;
    use OriginalLanguageTrait;
    use MarcFormatTrait;
    use FivTrait;

    /**
     * Get all subject headings associated with this record.  Each heading is
     * returned as an array of chunks, increasing from least specific to most
     * specific.
     * @return array
     */
    public function getAllSubjectHeadings($extended = false): array
    {
        // These are the fields that may contain subject headings:
        $fields = ['600', '610', '611', '630', '648', '650', '651', '655',
            '656', '689'];
        return $this->getSubjectHeadings($fields);
    }

    /**
     * Get subject headings associated with this record.  Each heading is
     * returned as an array of chunks, increasing from least specific to most
     * specific.
     * @return array
     */
    public function getSubjectHeadings(array $fields): array
    {
        // This is all the collected data:
        $retval = [];

        // Try each MARC field one at a time:
        foreach ($fields as $field) {
            // Do we have any results for the current field?  If not, try the next.
            $results = $this->getFields($field);

            //TODO: Necessary?
            if (empty($results)) {
                continue;
            }

            // If we got here, we found results -- let's loop through them.
            foreach ($results as $result) {
                if(!is_array($result)) {
                    continue;
                }

                // Get all the chunks and collect them together:
                $subfields =$result['subfields'];
                foreach ($subfields as $subfield) {
                    // Numeric subfields are for control purposes and should not
                    // be displayed:
                    if (preg_match('/[a-z]/', $subfield['code'])) {
                        $retval[] = $subfield['data'];
                    }
                }
            }
        }

        // Send back everything we collected:
        return array_unique($retval);
    }

    /**
     * Get all subjects associated with this item. They are unique.
     * @return array
     */
    public function getRVKSubjectHeadings()
    {
        $rvkchain = [];
        foreach ($this->getMarcReader()->getFields('936') as $field) {
            if ($field['i1'] == 'r' && $field['i2'] == 'v') {
                foreach ($this->getSubfields($field, 'k') as $item) {
                    $rvkchain[] = $item;
                }
            }
        }
        return array_unique($rvkchain);
    }

    /**
     * Get all subjects associated with this item. They are unique.
     * @return array
     */
    public function getGNDSubjectHeadings()
    {
        $gnd = [];
        foreach ($this->getFields('689') as $field) {
            $sf2 = $this->getSubfield($field, '2');
            if ($sf2 == 'gnd') {
                $tmp = [];
                $id = 0;
                foreach ($field['subfields'] ?? [] as $subfield) {
                    $sfCode = $subfield['code'] ?? '';
                    $sfData = $subfield['data'] ?? '';
                    if (preg_match('/[a-z]/', $sfCode)) {
                        $tmp[$sfCode] = $sfData;
                    } elseif ($sfCode == 0
                        && preg_match('/\(DE-588\)/', $sfData)
                    ) {
                        $id = preg_replace('/\(.*\)/','',  $sfData);
                    }

                }
                $gnd[$id] = [
                    'type' => 'gnd',
                    'data' => $this->addDelimiterChars($tmp)
                ];
            }
        }
        return $gnd;
    }

    /** Get all STandardtheaurus Wirtschaft keywords
     *
     * @return array
     */
    public function getSTWSubjectHeadings()
    {
        // Disable this output
        $return = [];
        foreach ($this->getFields('650') as $field) {
            if ($this->getSubfield($field, '2') == 'stw') {
                $return[] = $this->getSubfield($field, 'a');
            }
        }
        return array_unique($return);
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
        foreach ($this->getFields('084') as $field) {
            if(!is_array($field)) {
                continue;
            }

            //Keys for arrays should not be empty
            $sfa = $this->getSubfield($field, 'a');
            if(empty($sfa)) {
                continue;
            }

            if (strtolower($this->getSubfield($field, '2')) == 'rvk') {
                $title = [];
                foreach ($this->getSubfields($field, 'k') as $item) {
                    $title[] = htmlentities($item);
                }
                $notationList[$sfa] = $title;
            }
        }
        foreach ($this->getFields('936') as $field) {
            if(!is_array($field)) {
                continue;
            }
            $sfa = $this->getSubfield($field, 'a');
            if (empty($sfa)) {
                continue;
            }
            if ($field['i1'] == 'r' && $field['i2'] == 'v') {
                $title = [];
                foreach ($this->getSubfields($field, 'k') as $item) {
                    $title[] = htmlentities($item);
                }
                $notationList[$sfa] = $title;
            }
        }
        return $notationList;
    }

    /**
     * @param string $type all, main_topic, partial_aspect
     *
     * @return array
     *
     */
    public function getFivSubjects(string $type = 'all')
    {
        $notationList = [];

        $ind2 = null;
        if ($type === 'main_topics') {
            $ind2 = 0;
        } elseif ($type === 'partial_aspects') {
            $ind2 = 1;
        }

        foreach ($this->getFields('938') as $field) {
            $sf2 = $this->getSubfield($field, '2');
            if ($field['i1'] == 1
                && (empty($sf2) || $sf2 != 'gnd')
                && ((isset($ind2) && $field['i2']== $ind2) || !isset($ind2))
            ) {
                $sfa = $this->getSubfield($field, 'a');
                $data = preg_replace('/!.*!|:/i', '', $sfa);
                $notationList[] = $data;
            }
        }
        return $notationList;
    }

    /**
     * Get the date coverage for a record which spans a period of time (i.e. a
     * journal).  Use getPublicationDates for publication dates of particular
     * monographic items.
     * @return array
     */
    public function getDateSpan(): array
    {
        return $this->getFieldArray('362', ['a'], false);
    }

    /**
     * Get an array of all ISBNs associated with the record (may be empty).
     * @return array
     */
    public function getISBNs(): array
    {
        //isbn = 020az:773z
        $isbn = array_merge(
            $this->getFieldArray('020', ['a', 'z', '9'], false),
            $this->getFieldArray('773', ['z'], false)
        );
        return array_unique($isbn);
    }

    /**
     * Get an array of all ISSNs associated with the record (may be empty).
     * @return array
     */
    public function getISSNs(): array
    {
        // issn = 022a:440x:490x:730x:773x:776x:780x:785x
        $issn = array_merge(
            $this->getFieldArray('022', ['a'], false),
            $this->getFieldArray('029', ['a'], false),
            $this->getFieldArray('440', ['x'], false),
            $this->getFieldArray('490', ['x'], false),
            $this->getFieldArray('730', ['x'], false),
            $this->getFieldArray('773', ['x'], false),
            $this->getFieldArray('776', ['x'], false),
            $this->getFieldArray('780', ['x'], false),
            $this->getFieldArray('785', ['x'], false)
        );
        return array_unique($issn);
    }

    /**
     * Get a LCCN, normalised according to info:lccn
     * @return string
     */
    public function getLCCN()
    {
        //lccn = 010a, first
        return $this->getFirstFieldValue('010', ['a']);
    }

    /**
     * Get a note about languages and text
     * @return string
     */
    public function getNote()
    {
        return $this->getFirstFieldValue('546', ['a']);
    }

    /**
     * Get an array of notes "Enthaltene Werke" for the Notes-Tab.
     * @return array
     */
    public function getNotes()
    {
        $notesCodes = ['501', '505'];
        $notes = [];
        foreach ($notesCodes as $nc) {
            $tmp = $this->getFieldArray($nc, ['a', 't', 'r'], true, ', ');
            $notes = array_merge($notes, $tmp);
        }
        return $notes;
    }

    /**
     * Get an array of notes "Enthaltene Werke" for the Notes-Tab.
     * @return array
     */
    public function getMusicalCast()
    {
        $castCodes = ['937'];
        $cast = [];
        foreach ($castCodes as $cc) {
            $tmp = $this->getFieldArray($cc, ['d', 'e', 'f'], true, ' / ');
            $cast = array_merge($cast, $tmp);
        }
        return $cast;
    }

    /**
     * Get an array of newer titles for the record.
     * @return array
     */
    public function getNewerTitles()
    {
        //title_new = 785ast
        return $this->getFieldArray('785', ['a', 's', 't']);
    }

    /**
     * Get the OCLC number of the record.
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
     * @return array
     */
    public function getPhysicalDescriptions()
    {
        return $this->getFieldArray('300', ['a', 'b', 'c', 'e', 'f', 'g'], false);
    }

    /**
     * Get an array of previous titles for the record.
     * @return array
     */
    public function getPreviousTitles()
    {
        //title_old = 780ast
        return $this->getFieldArray('780', ['a', 's', 't']);
    }

    /**
     * Get the publication dates of the record.  See also getDateSpan().
     * @return array
     */
    public function getPublicationDates()
    {
        $return = [];
        $years = [];
        $f008 = $this->getField('008');
        $matches = [];
        if (is_string($f008)) {
            preg_match('/^(\d{2})(\d{2})(\d{2})([a-z])(\d{4})/', $f008, $matches);
        }
        if (array_key_exists(5, $matches)) {
            $years[] = $matches[5];
        }
        // if there's still no year, we parse it out of 260'
        if (count($years) == 0) {
            $fields = [
                260 => 'c',
                264 => 'c',
            ];
            $years = $this->getFieldsArray($fields);
            //TODO: getFieldsArray still correct?
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
     * @return array
     */
    public function getSummary()
    {
        $summaryCodes = ['502', '505', '515', '520'];
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
     *                     default).
     *
     * @return string|array|bool
     */
    public function getThumbnail($size = 'small')
    {
        $arr = [];
        $arrSizes = ['small', 'medium', 'large'];
        $isbn = $this->getCleanISBN();
        $ean = $this->getGTIN();
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
        } //journals and other media  - almost always have no cover
        else {
            return false;
        }
    }

    /**
     * return GTIN Code
     * @return string
     */
    public function getGTIN()
    {
        $gtin = $this->getFieldArray("024", ['a']);
        return array_shift($gtin);
    }

    /**
     * Get the text of the part/section portion of the title.
     * @return string
     */
    public function getTitleSection()
    {
        return $this->getFirstFieldValue('245', ['n', 'p']);
    }

    /**
     * Get the statement of responsibility that goes with the title (i.e. "by John
     * Smith").
     * @return string
     */
    public function getTitleStatement()
    {
        return $this->getFirstFieldValue('245', ['c']);
    }

    /**
     * Get an array of lines from the table of contents.
     * @return array
     */
    public function getTOC()
    {
        return $this->fields['contents'] ?? [];
    }

    /**
     * Return an array of associative URL arrays with one or more of the following
     * keys:
     * <li>
     *   <ul>desc: URL description text to display (optional)</ul>
     *   <ul>url: fully-formed URL (required if 'route' is absent)</ul>
     *   <ul>route: VuFind route to build URL with (required if 'url' is absent)</ul>
     *   <ul>routeParams: Parameters for route (optional)</ul>
     *   <ul>queryString: Query params to append after building route (optional)</ul>
     * </li>
     * @return array
     */
    public function getURLs(): array
    {
        //url = 856u:555u

        $urls = [];
        $urlFields = array_merge(
            $this->getFields('856'),
            $this->getFields('555')
        );

        // Special case Proquest eBooks for DE-950
        $isils = $this->getFieldArray('924', ['b'], false);
        $is950 = in_array('DE-950', $isils);

        foreach ($urlFields as $field) {
            if(!is_array($field)) {
                continue;
            }

            $url = [];

            //If the url is empty, we want to continue
            $sfu = $this->getSubfield($field, 'u');
            if (empty($sfu)) {
                continue;
            }

            $ind1 = $field['i1'];
            $ind2 = $field['i2'];

            //  we don't want to show licensed content
            //  ind1,2 = 4,0 is probably lincensed content.
            //  only if we find a kostenfrei in |z, we use the link
            //  special case: DE-950 Proquest links are shown
            if (!$is950 && $ind1 == 4 && $ind2 == 0) {
                $sfz = $this->getSubfield($field, 'z');
                if(!str_contains(strtolower($sfz), 'kostenfrei')) {
                    continue;
                }
                //TODO: Can this be deleted?
//                if (is_object($sfz)) {
//                    if (stripos($sfz->getData(), 'Kostenfrei') === false) {
//                        continue;
//                    }
//                } else {
//                    continue;
//                }
            }


            $url['url'] = $this->getSubfield($field, 'u');

            // add urn:nbn Resolver baseurl if missing
            if (str_contains($url['url'], 'urn:nbn')
                && !str_contains($url['url'], 'http')
            ) {
                $url['desc'] = $url['url'];
                $url['url'] = 'https://nbn-resolving.org/' . $url['url'];
            }

            // add hdl: Resolver baseurl if missing
            $sf2 = $this->getSubfield($field, '2');
            if ($sf2 === 'hdl' && !str_contains($url['url'], 'http')) {
                $url['desc'] = $url['url'];
                $url['url'] = 'https://hdl.handle.net/' . $url['url'];
            }

            //TODO: Cornelius fragen
            if (($sfu = $this->getSubfield($field, '3')) && strlen($sfu) > 2) {
                $url['desc'] = $sfu;
            } elseif ($sfu = $this->getSubfield($field, 'y')) {
                $url['desc'] = $sfu;
            } elseif (($sfu = $this->getSubField($field,'z')) && strpos('Kostenfrei', $sfu) !== false) {
                // x is marked as nonpublic!
                $url['desc'] = 'Full Text';
            } elseif (($sfu = $this->getSubField($field,'n'))) {
                $url['desc'] = $sfu;
            } elseif ($ind1 == 4 && ($ind2 == 1 || $ind2 == 0)) {
                $url['desc'] = 'Online Access';
            } elseif ($ind1 == 4 && ($ind2 == 1 || $ind2 == 0)) {
                $url['desc'] = 'More Information';
            }
            $urls[] = $url;
        }
        return array_unique($urls, SORT_REGULAR);
    }

    /**
     * @return string
     */
    public function getConsortium()
    {
        // determine network based on two different sources
        $consortium1 = $this->getFirstFieldValue(924, ['c']);
        $consortium1 = explode(' ', $consortium1);
        $consortium2 = $this->fields['consortium'];
        $consortium = $this->fields['consortium'];

        foreach ($consortium as $k => $con) {
            if (!empty($con)) {
                $mapped = $this->mainConfig->mapNetwork($con);
                if (!empty($mapped)) {
                    $consortium[$k] = $mapped;
                }
            } else {
                unset($consortium[$k]);
            }
        }
        $consortium_unique = array_unique($consortium);

        $string = implode(", ", $consortium_unique);
        return $string;
    }

    /**
     * Get a sortable title for the record (i.e. no leading articles).
     * @return string
     */
    public function getSortTitle()
    {
        return $this->fields['title_sort'] ?? parent::getSortTitle();
    }

    /**
     * Get longitude/latitude text (or false if not available).
     * @return string|bool
     */
    public function getLongLat()
    {
        return $this->fields['long_lat'] ?? false;
    }

    /**
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
     * @return array
     */
    public function getRealTimeHoldings()
    {
        if ($this->mainConfig->isIsilSession() && !$this->mainConfig->hasIsilSession()) {
            return [];
        } else {
            return $this->hasILS() ? $this->holdLogic->getHoldings(
                $this->getUniqueID(),
                $this->getConsortialIDs()
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

        if ($this->isArticle() ||
            $this->isElectronicBook() ||
            $this->isSerial() ||
            $this->isCollection()
        ) {
            return false;
        }
        return true;
    }

    public function getNetwork()
    {
        return 'NoNetwork';
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
     * Get the institutions holding the record.
     * @return array
     */
    public function getInstitutions()
    {
        return $this->getFieldArray('924', ['b'], false);
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
                        $to = $tmp[$i + 1] ?? null;
                        $set[] = [
                            'from' => isset($from) ? (int)$from : null,
                            'to' => isset($to) ? (int)$to : null,
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
     * @throws Exception
     */
    public function getLibraries()
    {
        return $this->getFieldArray(924, ['b']);
    }

    /**
     * Return system requirements
     */
    public function getSystemDetails()
    {
        return $this->getFieldArray('538', ['a'], true);
    }

    /**
     * Returns an array of related items for multipart results, including
     * its own id
     * @return array
     */
    public function getIdsRelated()
    {
        return $this->getContainerIds();
    }

    public function getRelatedEditions()
    {
        $related = [];
        # 775 is RAK and 776 RDA *confused*
        $f77x = array_merge(
            $this->getFields('775'),
            $this->getFields('776')
        );
        foreach ($f77x as $field) {
            $tmp = [];
            foreach ($field['subfields'] ?? [] as $subfield) {
                switch ($subfield['code']) {
                    case 'i':
                        $label = 'description';
                        break;
                    case 't':
                        $label = 'title';
                        break;
                    case 'w':
                        $label = 'id';
                        break;
                    case 'a':
                        $label = 'author';
                        break;
                    default:
                        $label = 'unknown_field';
                }
                if (!array_key_exists($label, $tmp)) {
                    $tmp[$label] = $subfield['data'];
                }
                if (!array_key_exists('description', $tmp)) {
                    $tmp['description'] = 'Parallelausgabe';
                }
            }
            // exclude DNB records
            if (isset($tmp['id']) && !str_contains($tmp['id'], 'DE-600')) {
                $related[] = $tmp;
            }
        }
        return $related;
    }

    /**
     * Returns Volume number
     * @return String
     */
    public function getVolumeNumber()
    {
        $fields = [
            830 => ['v'],
            773 => ['g']
        ];
        $volumes = preg_replace("/[\/,]$/", "", $this->getFieldsArray($fields));
        return array_shift($volumes);
    }

    /**
     * get local Urls from 924|k and the correspondig linklabel 924|l
     * - $924 is repeatable
     * - |k is repeatable, |l aswell
     * - we can have more than one isil ?is this true? maybe allways the first isil
     * - different Urls from one instition may have different issues (is this true?)
     * @return array
     */
    public function getLocalUrls()
    {
        $localUrls = [];
        $addedUrls = [];

        $holdings = $this->getLocalHoldings();
        $isils = $this->mainConfig->getIsils();
        $isils4local = $this->mainConfig->get('Site')->get('isil_local_url');

        if (empty($isils4local)) {
            $isils = [array_shift($isils)];
        } else {
            $isils = explode(',', $isils4local);
        }

        /**
         * Anonymous function, called bellow. It handles ONE url.
         *
         * @param $link
         * @param $label
         */
        $handler = function ($isil, $link, $label) use (&$addedUrls, &$isils) {

            // Is there a label?  If not, just use the URL itself.
            if (empty($label)) {
                $label = $link;
            }
            $tmp = null;

            $link = str_replace(
                'http://dx.doi.org',
                'https://doi.org',
                $link
            );

            // Prevent adding the same url multiple times
            if (!in_array($link, $addedUrls) && !empty($link)
                && in_array($isil, $isils)
            ) {
                $tmp = [
                    'isil' => $isil,
                    'url' => $link,
                    'label' => $label
                ];
            }
            $addedUrls[] = $link;
            return $tmp;
        };

        foreach ($holdings as $holding) {
            $address = $holding['url'] ?? null;
            $label = $holding['url_label'] ?? null;
            $isilcurrent = $holding['isil'] ?? null;

            if (is_array($address)) {
                for ($i = 0; $i < count($address); $i++) {
                    $localUrls[] = $handler($isilcurrent, $address[$i], $label[$i] ?? null);
                }
            } else {
                $localUrls[] = $handler($isilcurrent, $address, $label);
            }
        }
        return array_filter($localUrls);
    }

    /**
     * This method supports wildcard operators in ISILs.
     * @return array
     */
    public function getLocalHoldings()
    {
        $holdings = [];
        $f924 = $this->getField924();
        $isils = $this->mainConfig->getIsilAvailability();

        if (count($isils) == 0) {
            return [];
        }

        // Building a regex pattern
        foreach ($isils as $k => $isil) {
            $isils[$k] = '^' . preg_quote($isil, '/') . '$';
        }
        $pattern = implode('|', $isils);
        $pattern = '/' . str_replace('\*', '.*', $pattern) . '/';
        foreach ($f924 as $fields) {
            if (is_string($fields['isil']) && preg_match($pattern, $fields['isil'])) {
                $holdings[] = $fields;
            }
        }

        return $holdings;
    }

    public function getHoldingIsils()
    {
        return $this->getHoldingIsilsFromField('924', 'b');
    }

    /**
     * Returns url  from 856|u
     * @return String
     */
    public function getPDALink()
    {
        $fields = [
            830 => ['v'],
            773 => ['g']
        ];
        $volumes = preg_replace("/[\/,]$/", "", $this->getFieldsArray($fields));
        return array_shift($volumes);
    }

    /**
     * Has this record holdings in field 924
     * @return boolean
     */
    public function hasLocalHoldings()
    {
        $holdings = $this->getLocalHoldings();
        return count($holdings) > 0;
    }

    /**
     * Get an array of remarks for the Details-Tab.
     * @return array
     */
    public function getRemarks()
    {
        $remarkCodes = ['511'];
        $remarks = [];
        foreach ($remarkCodes as $rc) {
            $tmp = $this->getFieldArray($rc, ['a'], true, ', ');
            $remarks = array_merge($remarks, $tmp);
        }
        return $remarks;
    }

    /**
     *  Scale of a map
     */
    public function getScale()
    {
        $scale = $this->getFieldArray("255", ['a']);
        if (empty($scale)) {
            $scale = $this->getFieldArray("034", ['b']);
        }
        return array_shift($scale);
    }

    /**
     * get 830|w if it exists with (DE-627)-Prefix
     * @return array
     */
    public function getSeriesIds()
    {
        $fields = [
            830 => ['w'],
        ];
        $ids = [];
        $array_clean = [];
        $array = $this->getFieldsArray($fields);
        foreach ($array as $subfields) {
            $ids = explode(' ', $subfields);
            if (preg_match('/^((?!DE-576|DE-609|DE-600.*-).)*$/', $ids[0])) {
                $array_clean[] = $ids[0];
            }
        }
        return $array_clean;
    }

    /**
     * This method is basically a duplicate of getAllRecordLinks but
     * much easier designer and works well with German library links
     * @return array
     */
    public function getParallelEditions()
    {
        $retval = [];
        foreach ($this->getFields(776) as $field) {
            if(!is_array($field)) {
                continue;
            }
            $tmp = [];
            if ($field['i1'] == 0) {

                $sfw = $this->getSubfield($field, 'w');
                if(!empty($sfw)) {
                    $tmp['ppn'] = $sfw;
                }

                $sfi = $this->getSubfield($field, 'i');
                if (!empty($sfi)) {
                    $tmp['prefix'] = $sfi;
                }

                $sft = $this->getSubfield($field, 't');
                if(!empty($sft)) {
                    $tmp['label'] = $sft;
                }

                $sfn = $this->getSubfield($field, 'n');
                if(!empty($sfn)) {
                    $tmp['postfix'] = $sfn;
                }
            }
            if (isset($tmp['ppn'], $tmp['label'])) {
                $retval[] = $tmp;
            }
        }
        return array_filter($retval);
    }

    /**
     * Get an array of bibliographic relations for the record.
     * @return array
     */
    public function getBiblioRelations()
    {
        return $this->getFieldArray('787', ['i', 'a', 't', 'd']);
    }

    /**
     * get 787|w if it exists with (DE-627)-Prefix
     * @return array
     */
    public function getBiblioRelatonsIds()
    {
        $fields = [
            787 => ['w'],
        ];
        $ids = [];
        $array_clean = [];
        $array = $this->getFieldsArray($fields);
        foreach ($array as $subfields) {
            $ids = explode(' ', $subfields);
            if (preg_match('/^((?!DE-576|DE-609|DE-600.*-).)*$/', $ids[0])) {
                $array_clean[] = $ids[0];
            }
        }
        return $array_clean;
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
        $params['rft.isbn'] = (string)$this->getCleanISBN();
        return array_filter($params);
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
     * Returns Volume title
     * @return String
     */
    public function getVolume()
    {
        $fields = [
            245 => ['p'],
        ];
        $volumes = preg_replace("/\/$/", "", $this->getFieldsArray($fields));
        return array_shift($volumes);
    }

    /**
     * Get the edition of the current record.
     * @return string
     */
    public function getEdition()
    {
        return $this->getFirstFieldValue('250', ['a']);
    }

    /**
     * Get OpenURL parameters for an article.
     * @return array
     */
    protected function getArticleOpenUrlParams()
    {
        $params = $this->getDefaultOpenUrlParams();
        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
        $params['rft.genre'] = $this->isContainerMonography() ? 'bookitem' : 'article';
        $params['rft.issn'] = (string)$this->getCleanISSN();
        // an article may have also an ISBN:
        $params['rft.isbn'] = (string)$this->getCleanISBN();
        $params['rft.volume'] = $this->getContainerVolume();
        $params['rft.issue'] = $this->getContainerIssue();
        $params['rft.date'] = $this->getContainerYear();
        if (str_contains($this->getContainerPages(), '-')) {
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
     * @return array
     */
    protected function getJournalOpenURLParams()
    {
        $places = $this->getPlacesOfPublication();
        $params = $this->getDefaultOpenUrlParams();
        $publishers = $this->getPublishers();

        $params['rft_val_fmt'] = 'info:ofi/fmt:kev:mtx:journal';
        $params['rft.issn'] = (string)$this->getCleanISSN();
        $params['rft.jtitle'] = $this->getTitle();
        $params['rft.genre'] = 'journal';
        $params['rft.place'] = array_shift($places);
        $params['rft.pub'] = array_shift($publishers);
        // zdbid is allowed in pid zone only - it is moved there
        // in OpenURL helper
        $params['pid'] = 'zdbid=' . $this->getZdbId();

        return array_filter($params);
    }

    /**
     * Get the item's place of publication.
     * @return array
     */
    public function getPlacesOfPublication()
    {
        $fields = [
            260 => 'a',
            264 => 'a',
        ];

        $places = [];
        foreach ($fields as $no => $subfield) {
            //TODO: Use getFieldsArray?
            $raw = $this->getFieldArray($no, (array)$subfield, false);
            if (count($raw) > 0 && !empty($raw[0])) {
                if (is_array($raw)) {
                    foreach ($raw as $p) {
                        $places[] = $p;
                    }
                } else {
                    $places[] = $raw;
                }
            }
        }
        foreach ($places as $k => $place) {
            $replace = [' :'];
            if (is_array($place)) {
                $place = implode(', ', $place);
                $places[$k] = str_replace($replace, '', $place);
            } else {
                $places[$k] = str_replace($replace, '', $place);
            }
        }
        return $places;
    }

    /**
     * Get ZDB ID if available
     * @return string
     */
    public function getZdbId()
    {
        $zdb = '';
        $substr = '';
        $matches = [];
        $consortial = $this->getConsortialIDs();
        foreach ($consortial as $id) {
            preg_match('/\(DE-\d{3}\)ZDB(.*)/', $id, $matches);
            if (!empty($matches) && $matches[1] !== '') {
                $zdb = $matches[1];
            }
        }

        // Pull ZDB ID out of recurring field 016
        foreach ($this->getFields('016') as $field) {
            if(!is_array($field)) {
                continue;
            }

            $isil = $data = '';
            foreach ($field['subfields'] ?? [] as $subfield) {
                if ($subfield['code'] == 'a') {
                    $data = $subfield['data'];
                } elseif ($subfield['code'] == '2') {
                    $isil = $subfield['data'];
                }
            }
            if ($isil == 'DE-600') {
                $zdb = $data;
            }
        }

        return $zdb;
    }

    /**
     * @return bool
     */
    public function isEPflicht() : bool
    {
        $fields = $this->getFieldArray('912', ['a']);
        return in_array('EPF-BW-GESAMT', $fields, true) && !$this->isBLB();
    }


    private function isBLB(): bool
    {
        $f583 = $this->getFields('583');
        foreach ($f583 as $field) {
            $sff = $this->getSubfield($field, 'f');
            $sf5 = $this->getSubfield($field, '5');
            if(($sff === 'PEBW') && ($sf5 === 'DE-31')) {
                return true;
            }
        }
        return false;
    }


    /**
     * @return bool
     */
    public function isLFER() : bool
    {
        $fields = $this->getField924();
        foreach ($fields as $field) {
            if ($field['isil'] === 'LFER') {
                return true;
            }
        }

        return false;
    }


}
