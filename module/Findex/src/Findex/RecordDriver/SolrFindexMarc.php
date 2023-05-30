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

}
