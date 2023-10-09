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
use Bsz\RecordDriver\Constants;
use Bsz\RecordDriver\ContainerTrait;
use Bsz\RecordDriver\FivTrait;
use Bsz\RecordDriver\HelperTrait;
use Bsz\RecordDriver\MarcAuthorTrait;
use Bsz\RecordDriver\MarcFormatTrait;
use Bsz\RecordDriver\SolrMarc;
use Bsz\RecordDriver\SubrecordTrait;
use VuFind\RecordDriver\DefaultRecord;
use VuFind\RecordDriver\IlsAwareTrait;
use VuFind\RecordDriver\MarcAdvancedTrait;
use VuFind\RecordDriver\MarcReaderTrait;

/**
 * SolrMarc class for Findex records
 *
 * @author amzar
 */
class SolrFindexMarc extends SolrMarc implements Constants
{
    use IlsAwareTrait;
    use MarcReaderTrait;
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
        // TODO
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
            $this->getMarcRecord()->getFields('856'),
            $this->getMarcRecord()->getFields('555')
        );
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
        $f980 = $this->getMarcRecord()->getFields('980');

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
            if ($suba && $field->getIndicator(1) == 'r'
                && $field->getIndicator(2) == 'v'
            ) {
                $title = [];
                foreach ($field->getSubFields('k') as $item) {
                    $title[] = htmlentities($item->getData());
                }
                $notationList[$suba->getData()] = $title;
            }
        }
        return $notationList;
    }

    public function getBibliographies()
    {
        $return = [];
        $m935 = $this->getMarcRecord()->getFields('935');
        foreach ($m935 as $field) {
            foreach ($field->getSubfields('a') as $suba) {
                $content = strtoupper($suba->getData());
                if ($this->mainConfig->is($content)) {
                    $return[] = $content;
                }
            }
        }
        return $return;
    }

    public function getLocalSubjects()
    {
        $fields = $this->getMarcRecord()->getFields('982');
        $isils = $this->mainConfig->getIsils();
        $output = [];
        foreach ($fields as $index => $field) {
            $isil = $field->getSubfield('x')->getData();
            if (in_array($isil, $isils)) {
                $output[] = $field->getSubfield('a')->getData();
            }
        }
        return $output;
    }

    public function getParallelEditions()
    {
        $retval = [];
        foreach ($this->getMarcRecord()->getfields(776) as $field) {
            $tmp = [];
            if ($field->getIndicator(1) == 0) {
                $tmp['ppn'] = $field->getSubfield('w') ? $field->getSubfield('w')->getData() : null;
                if ($tmp['ppn'] !== null) {
                    $tmp['ppn'] = preg_replace('/\(.*\)(.*)/', '$1', $tmp['ppn']);
                }
                if ($field->getSubfield('i')) {
                    $tmp['prefix'] = $field->getSubfield('i')->getData();
                }
                if ($field->getSubfield('t')) {
                    $tmp['label'] = $field->getSubfield('t')->getData();
                }
                if ($field->getSubfield('n')) {
                    $tmp['postfix'] = $field->getSubfield('n')->getData();
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
        $f583 = $this->getMarcRecord()->getField('583');
        $sff = $f583->getSubfield('f')->getData();
        $sf5 = $f583->getSubfield('5')->getData();
        return ($sff === 'PEBW') && ($sf5 === 'DE-31');
    }

    /**
     * @return bool
     */
    public function isLFER() : bool
    {
        $fields = $this->getFieldArray('912', ['a']);
        return in_array('ISIL_DE-LFER', $fields);
    }


}
