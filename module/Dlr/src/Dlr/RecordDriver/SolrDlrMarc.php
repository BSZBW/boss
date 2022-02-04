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
namespace Dlr\RecordDriver;

use Bsz\RecordDriver\ContainerTrait;
use Bsz\RecordDriver\MarcAuthorTrait;
use Bsz\RecordDriver\MarcFormatTrait;
use Bsz\RecordDriver\SolrMarc;
use File_MARC_Exception;
use VuFind\RecordDriver\IlsAwareTrait;

/**
 * Description of SolrDlrmarc
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SolrDlrMarc extends SolrMarc
{
    use ContainerTrait;
    use IlsAwareTrait;
    use MarcAuthorTrait;
    use MarcFormatTrait;

    /**
     * Get all subjects associated with this item. They are unique.
     * @return array
     * @throws File_MARC_Exception
     */
    public function getRVKSubjectHeadings()
    {
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
     * Get formats from
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
     * As out fiels 773 does not contain any further title information we need
     * to query solr again
     * @return array
     */
    public function getContainer()
    {
        $relId = $f773 = $this->getFieldArray(773, ['w']);

        if (null === $this->container && is_array($relId) && count($relId) > 0) {
            $this->container = [];
            foreach ($relId as $k => $id) {
                $relId[$k] = 'ctrlnum:"(Horizon)' . $id . '"';
            }
            $params = [
                'lookfor' => implode(' OR ', $relId),
            ];
            // QnD
            // We need the searchClassId here to get proper filters
            $searchClassId = 'Solr';

            $results = $this->runner->run($params, $searchClassId);
            $this->container = $results->getResults();
        }
        return $this->container;
    }

    /**
     * @return array
     * @throws File_MARC_Exception
     */
    public function getRelatedItems()
    {
        $related = [];
        $f774 = $this->getMarcRecord()->getFields('774');
        foreach ($f774 as $field) {
            $tmp = [];
            $subfields = $field->getSubfields();
            foreach ($subfields as $subfield) {
                switch ($subfield->getCode()) {
                    case 'd':
                        $label = 'edition';
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
                    $tmp[$label] = $subfield->getData();
                }
            }
            $related[] = $tmp;
        }
        return $related;
    }

    /**
     * @return bool
     */
    public function supportsAjaxStatus()
    {
        return true;
    }

    /**
     * Get an array of all the languages associated with the record.
     * @return array
     * @throws File_MARC_Exception
     */
    public function getLanguages(): array
    {
        $languages = [];
        $fields = $this->getMarcRecord()->getFields('041');
        $m008 = $this->getMarcRecord()->getField('008');

        foreach ($fields as $field) {
            foreach ($field->getSubFields('a') as $sf) {
                $languages[] = $sf->getData();
            }
        }

        if ($m008) {
            $data = $m008->getData();
            preg_match('/.{35}([a-z]{3})/', $m008->getData(), $matches);

            if (isset($matches[1])) {
                $languages[] = $matches[1];
            }
        }

        return $languages;
    }

    /**
     *
     * @return array
     */
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
        $authors = $this->getPrimaryAuthors();
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
        $params['rft.volume'] = $this->getContainerVolume();

        $publishers = $this->getPublishers();
        if (count($publishers) > 0) {
            $params['rft.pub'] = $publishers[0];
        }

        $params['rft.edition'] = $this->getEdition();
        $params['rft.isbn'] = (string)$this->getCleanISBN();
        return array_filter($params);
    }

    /**
     * @return array
     */
    public function getHumanReadablePublicationDates()
    {
        $dates = parent::getHumanReadablePublicationDates();
        foreach ($dates as $k => $date) {
            preg_match('/^(\d{4})/', $date, $matches);
            $dates[$k] = $matches[1] ?? null;
        }
        return $dates;
    }

    /**
     * Sanitize and clean urls
     * @return array
     */
    public function getUrls()
    {
        $parent = parent::getUrls();
        $forbidden = [
            'lib\.myilibrary\.com',
            'lib\.myilibrary\.com',
            'lib\.myilibrary\.com'
        ];
        $regex = '/'.implode('|', $forbidden).'/';
        foreach ($parent as $k => $url) {
            if (preg_match('/^http/', $url['desc'])) {
                $contents = parse_url($url['url']);
                // avoid ugly url strings in output
                $parent[$k]['desc'] = $contents['host'];
            }
            if (preg_match($regex, $url['url'])) {
                unset($parent[$k]);
            }
        }
        return $parent;
    }
}
