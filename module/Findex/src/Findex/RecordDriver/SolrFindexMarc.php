<?php
/*
 * Copyright 2022 (C) Bibliotheksservice-Zentrum Baden-
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

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Findex\RecordDriver;

use Bsz\Exception;
use Bsz\RecordDriver\AdvancedMarcReaderTrait;
use Bsz\RecordDriver\Constants;
use Bsz\RecordDriver\ContainerTrait;
use Bsz\RecordDriver\FivTrait;
use Bsz\RecordDriver\HelperTrait;
use Bsz\RecordDriver\MarcAuthorTrait;
use Bsz\RecordDriver\MarcFormatTrait;
use Bsz\RecordDriver\SolrMarc;
use Bsz\RecordDriver\SubrecordTrait;
use VuFind\RecordDriver\DefaultRecord;
use VuFind\RecordDriver\Feature\IlsAwareTrait;
use VuFind\RecordDriver\Feature\MarcAdvancedTrait;
use VuFind\RecordDriver\Feature\MarcReaderTrait;

/**
 * SolrMarc class for Findex records
 *
 * @author amzar
 */
class SolrFindexMarc extends SolrMarc implements Constants
{
    use IlsAwareTrait;
    use AdvancedMarcReaderTrait;
    use MarcAdvancedTrait;
    use SubrecordTrait;
    use HelperTrait;
    use ContainerTrait;
    use MarcFormatTrait;
    use MarcAuthorTrait;
    use FivTrait;

    /**
     * Returns consortium
     * @return array
     * @throws Exception
     */
    public function getConsortium()
    {
        // determine network
        // GVK = GBV
        // SWB = SWB
        // ÖVK = GBV

        $consortium_unique = [];

        $consortium = DefaultRecord::tryMethod('getCollections');

        if ($consortium != null) {
            foreach ($consortium as $k => $con) {
                $mapped = $this->mainConfig->mapNetwork($con);
                if (!empty($mapped)) {
                    $consortium[$k] = $mapped;
                }
            }
            $consortium_unique = array_unique($consortium);
        }

        $string = implode(", ", $consortium_unique);
        return $string;
    }

    /**
     * Returns German library network shortcut.
     * @return string
     */
    public function getNetwork()
    {
        return 'KXP';
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
// TODO welche Felder bei Findex
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
// TODO, welche Felder bei Findex
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
        $urlFields = array_merge(
            $this->getFields('856'),
            $this->getFields('555')
        );
        foreach ($urlFields as $field) {
            if(!is_array($field)) {
                continue;
            }

            $url = [];

            $ind1 = $field['i1'];
            $ind2 = $field['i2'];

            //URL must not be empty
            $sfu = $this->getSubfield($field, 'u');
            if (empty($sfu)) {
                continue;
            }

            $url['url'] = $sfu;

            //TODO: = correct?
            if (($sfu = $this->getSubfield($field, '3')) && strlen($sfu) > 2) {
                $url['desc'] = $sfu;
            } elseif (($sfu = $this->getSubfield($field, 'y'))) {
                $url['desc'] = $sfu;
            } elseif (($sfu = $this->getSubfield($field, 'n'))) {
                $url['desc'] = $sfu;
            } elseif ($ind1 == 4 && ($ind2 == 1 || $ind2 == 0)) {
                $url['desc'] = 'Online Access';
            } elseif ($ind1 == 4 && ($ind2 == 1 || $ind2 == 0)) {
                $url['desc'] = 'More Information';
            }
            $urls[] = $url;
        }
        return $urls;
    }

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
                $array_clean[] = substr($ids[0], 8);
            }
        }
        return $array_clean;
    }

    /**
     * Get Content of 924 as array: isil => array of subfields
     * @return array
     *
     */
    public function getField980()
    {
        $f980 = $this->getFields('980');

        // map subfield codes to human-readable descriptions
        $mappings = [
            'a' => 'local_idn',
            'x' => 'isil',
            'c' => 'region',
            'd' => 'call_number',
            'k' => 'url',
            'l' => 'url_label',
            'z' => 'issue'
        ];

        $result = [];

        foreach ($f980 as $field) {
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
                if (str_contains($sub, ' | ')) {
                    $split = explode(' | ', $sub);
                    $arrsub[$k] = $split;
                }
            }
            $result[] = $arrsub;
        }
        return $result;
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
     * As out fiels 773 does not contain any further title information we need
     * to query solr again
     *
     * @return array
     * @throws Exception
     */
    public function getContainer()
    {
        if (count($this->container) == 0 &&
            ($this->isArticle() || $this->isPart())
        ) {
            $relId = $this->getContainerIds();

            $this->container = [];
            if (is_array($relId) && count($relId) > 0) {
                foreach ($relId as $k => $id) {
                    $id = preg_replace('/\(DE-627\)/', '', $id);
                    $relId[$k] = 'id:"' . $id . '"';
                }
                $params = [
                    'lookfor' => implode(' OR ', $relId),
                ];
                if (null === $this->runner) {
                    throw new Exception('Please attach a search runner first');
                }
                $results = $this->runner->run($params, 'Solr');
                $this->container = $results->getResults();
            }
        }
        return $this->container;
    }
    public function getIdsRelated()
    {
        return $this->getContainerIds();
    }
    public function getContainerIds()
    {
        $fields = [
            773 => ['w'],
            830 => ['w']
        ];
        $ids = [];
        $array = $this->getFieldsArray($fields);
        foreach ($array as $subfields) {
            $tmp = explode(' ', $subfields);
            foreach ($tmp as $id) {
                if ( preg_match('/^\(DE-627\)/', $id)) {
                    $ids[] = preg_replace('/\(.*\)/', '', $id);
                }
            }
        }
        return array_unique($ids);
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

            $sfa = $this->getSubfield($field, 'a');
            $sf2 = $this->getSubfield($field, '2');

            if(empty($sfa) || empty($sf2)) {
                continue;
            }

            if (strtolower($sf2) == 'rvk') {
                $title = [];
                foreach ($this->getSubfields($field, 'k') as $item) {
                    $title[] = htmlentities($item);
                }
                $notationList[$sfa] = $title;
            }
        }
        foreach ($this->getFields('936') as $field) {

            $sfa = $this->getSubfield($field, 'a');
            if(empty($sfa)) {
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

    public function getBibliographies()
    {
        $return = [];
        $f935 = $this->getFields('935');

        foreach ($f935 as $field) {
            if(!is_array($field)) {
                continue;
            }

            foreach ($this->getSubfields($field, 'a') as $sfa) {
                $content = strtoupper($sfa);
                if ($this->mainConfig->is($content)) {
                    $return[] = $content;
                }
            }
        }
        return $return;
    }

    public function getLocalSubjects()
    {
        $fields = $this->getFields('982');
        $isils = $this->mainConfig->getIsils();
        $output = [];
        foreach ($fields as $field) {
            if(!is_array($field)) {
                continue;
            }

            $isil = $this->getSubfield($field, 'x');
            if (in_array($isil, $isils)) {
                $output[] = $this->getSubfield($field, 'a');
            }
        }
        return $output;
    }

    public function getParallelEditions()
    {
        $retval = [];
        foreach ($this->getFields(776) as $field) {
            $tmp = [];
            if ($field['i1'] == 0) {

                $sfw = $this->getSubfield($field, 'w');
                if(!empty($sfw)) {
                    $tmp['ppn'] =  preg_replace('/\(.*\)(.*)/', '$1', $sfw);
                }

                $sfi = $this->getSubfield($field, 'i');
                if(!empty($sfi)) {
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
     * Get an array of physical descriptions of the item.
     * @return array
     */
    public function getPhysicalDescriptions()
    {
        return $this->getFieldArray('300', ['a', 'b', 'c', 'e', 'f', 'g'], false);
    }

    /**
     * @return bool
     */
    public function isEPflicht() : bool
    {
        $fields = $this->getFieldArray('912', ['a']);
        return in_array('EPF-BW-GESAMT', $fields) && !$this->isBLB();
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
        $fields = $this->getFieldArray('912', ['a']);
        return in_array('ISIL_DE-LFER', $fields);
    }

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

    public function getOldPrintGenre()
    {
        $retVal = [];
        foreach ($this->getFields('655') as $field) {
            if(!is_array($field)) {
                continue;

            }
            $sfa = $this->getSubfield($field, 'a');
            $sf2 = $this->getSubfield($field, '2');
            if ($sf2 == 'local' && !empty($sfa)) {
                $retVal[] = $sfa;
            }
        }
        return $retVal;
    }

    public function getKindContent()
    {
        $fields = array_merge(
            $this->getFields('655'),
            $this->getFields('348')
        );

        $map = [
            '655' => 'gnd-content',
            '348' => 'gnd-music'
        ];

        $retVal = [];
        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $sf2Req = $map[$field['tag']];
            if(empty($sf2Req) || $sf2Req !== $this->getSubfield($field, '2')) {
                continue;
            }

            foreach ($field['subfields'] as $sf) {
                if(preg_match('/^[a-zA-Z]$/', $sf['code'])) {
                    $retVal[] = $sf['data'];
                }
            }
        }

        return $retVal;
    }

    public function getFormattedMarcDetails($defaultField, $data)
    {
        $holdings = [];

        $f980 = $this->getFields('980');
        foreach ($f980 as $field) {
            if(!is_array($field)) {
                continue;
            }

            $sf1= $this->getSubfield($field, '1');

            $entry = [];

            $sfx = $this->getSubfield($field, 'x');
            if(empty($sfx)) {
                continue;
            }

            $entry['location'] = trim($sfx);

            $sfd = $this->getSubfield($field, 'd');
            if(!empty($sfd) && "--%%--" !== $sfd) {
                $entry['callnumber'] = $sfd;
            }

            $sff = $this->getSubfield($field, 'f');
            if(!empty($sff) && "--%%--" !== $sff) {
                $entry['storage'] = $sff;
            }

            $sfk = $this->getSubfield($field, 'k');
            if("--%%--" != $sfk) {
                $start = strpos($sfk, '***');
                if ($start !== false){
                    $sfk = substr($sfk, $start + 3);
                }
                if(!empty($sfk)) {
                    $entry['notes'] = $sfk;
                }
            }

            $sfg = $this->getSubfield($field, 'g');
            if(!empty($sfg) && "--%%--" !== $sfg) {
                if (isset($entry['notes'])) {
                    $entry['notes'] = $entry['notes'] ? $entry['notes'] . "<br>" . $sfg : $sfg;
                }else {
                    $entry['notes'] = $sfg;
                }
            }

            $sfj = $this->getSubfield($field, 'j');
            switch ($sfj) {
                case 'l':
                case "--%%--":
                    $entry['availability'] = 'c';
                    break;
                case 'a':
                    $entry['availability'] = 'a';
                    break;
                case 'k':
                    $entry['availability'] = 'b';
                    break;
                case 'n':
                    $entry['availability'] = 'N';
                    break;
                case 'e':
                    $entry['availability'] = 'e';
                    break;
            }

            if (!empty($entry)) {
                $holdings[$sfx][$sf1] = $entry;
            }
        }

        $f981 = $this->getFields('981');
        foreach ($f981 as $field) {
            if(!is_array($field)) {
                continue;
            }

            $sf1 = $this->getSubfield($field, '1');
            $sfx = $this->getSubfield($field, 'x');

            $sfr = $this->getSubfield($field, 'r');
            if(!empty($sfr) && "--%%--" !== $sfr
                && array_key_exists($sfx, $holdings)
                && array_key_exists($sf1, $holdings[$sfx])){
                $holdings[$sfx][$sf1]['link'] = $sfr;
            }
        }

        $retVal = [];
        foreach ($holdings as $k => $v) {
            $retVal = array_merge($retVal, array_values($v));
        }
        return $retVal;
    }

    public function getGNDSubjectHeadings()
    {
        $fields = $this->getMultiFields(['600', '610', '611', '630', '648', '650', '651', '655', '689']);

        $gnd = [];
        foreach ($fields as $field) {
            if(!is_array($field)) {
                continue;
            }

            $sf2 = $this->getSubfield($field, '2');
            if ($sf2 == 'gnd') {
                $tmp = [];

                $sf0 = $this->getSubfield($field, '0');
                if (!preg_match('/^\(DE-588\).+$/', $sf0)) {
                    continue;
                }

                $id = str_replace('(DE-588)', '', $sf0);
                if(array_key_exists($id, $gnd)) {
                    continue;
                }

                foreach ($field['subfields'] ?? [] as $subfield) {
                    $sfCode = $subfield['code'] ?? '';
                    $sfData = $subfield['data'] ?? '';
                    if (preg_match('/[a-z]/', $sfCode)) {
                        $tmp[$sfCode] = $sfData;
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

    public function getSystematics()
    {
        $fields = $this->getFields('084');

        $retVal = [];
        foreach ($fields as $field) {
            if(!is_array($field) || 'bwlb' !== $this->getSubfield($field, '2')) {
                continue;
            }

            $retVal = array_merge($retVal, $this->getSubfields($field, 'a'));
        }

        return $retVal;
    }

    public function getFreeKeywords()
    {
        $fields = $this->getMultiFields(['600', '610', '611', '630', '648', '650', '651', '655', '689']);
        $sf7Whitelist = ['(dpeaa)DE-631', '(dpeaa)DE-24', '(dpeaa)DE-24/stga'];

        $data = [];
        foreach ($fields as $field) {
            if(!is_array($field)) {
                continue;
            }

            $sf2 = $this->getSubfield($field, '2');
            $sf7 = $this->getSubfield($field, '7');

            if($field['tag'] !== '648' && $sf2 !== 'gnd' && in_array($sf7, $sf7Whitelist)) {
                $data = array_merge($data, $this->getSubfieldsByRegex($field, '/^[a-z]$/'));
            } elseif ($field['tag'] == '648' && $field['i2'] == '7') {
                $data = array_merge($data, $this->getSubfieldsByRegex($field, '/^[a-z]$/'));
            }
        }
        $retVal = [];
        foreach ($data as $item) {
            $retVal[] = [
                'type' => 'free',
                'data' => $item
            ];
        }
        return $retVal;
    }

    public function getHoldingIsils(): array
    {
        return $this->getHoldingIsilsFromField('980', 'x');
    }

    public function getBibliographicalContext()
    {
        return $this->getContextFromField('787');
    }

    protected function getContextFromField(string $fieldTag): array
    {
        $retVal = [];
        foreach ($this->getFields($fieldTag) as $field) {
            $tmp = [];
            if ($field['i1'] == 0) {

                $sfw = $this->getSubfield($field, 'w');
                if(!empty($sfw)) {
                    $tmp['ppn'] =  preg_replace('/\(.*\)(.*)/', '$1', $sfw);
                }

                $sfi = $this->getSubfield($field, 'i');
                if(!empty($sfi)) {
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
                $retVal[] = $tmp;
            }
        }
        return array_filter($retVal);
    }

    /**
     * get local Urls from 981|r
     * @return array
     */
    public function getLocalUrls()
    {
        $isils = $this->mainConfig->getIsils();
        $isils4local = $this->mainConfig->get('Site')->get('isil_local_url');

        if (empty($isils4local)) {
            $isils = [array_shift($isils)];
        } else {
            $isils = explode(',', $isils4local);
        }

        $retVal = [];
        $urlsOnly = [];

        $f981 = $this->getFields('981');
        foreach ($f981 as $field) {
            if(!is_array($field)) {
                continue;
            }

            $sfr = $this->getSubfield($field, 'r');
            $sfx = $this->getSubfield($field, 'x');

            if(!empty($sfr) && in_array($sfx, $isils) && !in_array($sfr, $urlsOnly)) {
                $retVal[] = [
                    'url' => $sfr,
                    'label' => $sfr,
                    'isil' => $sfx
                ];
                $urlsOnly[] = $sfr;
            }

        }
        return $retVal;
    }

    public function isFromCollection(string $collCode): bool
    {
        $f912 = $this->getFields('912');
        foreach ($f912 as $field) {
            if(!is_array($field)) {
                continue;
            }
            if ($collCode == $this->getSubfield($field, 'a')) {
                return true;
            }
        }
        return false;
    }

}
