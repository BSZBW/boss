<?php

/**
 * swissbib / VuFind swissbib enhancements for Summon records
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 1/2/13
 * Time: 4:09 PM
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category swissbib_VuFind2
 * @package  RecordDriver
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */

namespace Swissbib\RecordDriver;

use VuFind\RecordDriver\Summon as VuFindSummon;

/**
 * Enhancement for swissbib Summon records
 *
 * @category swissbib_VuFind2
 * @package  RecordDrivers
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
class Summon extends VuFindSummon implements SwissbibRecordDriver
{

    /**
     * @return    String    Author name(s)
     */
    public function getAuthor()
    {
        $author = $this->getField('Author', '-');

        return is_array($author) ? implode('; ', $author) : $author;
    }

    /**
     * @return    Array
     */
    public function getURI()
    {
        return $this->getField('URI');
    }

    /**
     * @return string ??
     * return 360-summon-link (field 'link')
     */
    public function getLink()
    {
        return $this->getField('link');
    }

    /**
     * @return    Boolean
     */
    public function hasDirectLink()
    {
        return in_array('DirectLink', $this->getLinkModel());
    }

    /**
     * @return    Boolean
     */
    public function hasFulltext()
    {
        return 1 === intval($this->getField('hasFullText'));
    }

    /**
     * @return  string
     */
    public function getImprint()
    {
        $imprint = '';
        $pub_place = $this->getPlacesOfPublication();
        if (is_array($pub_place) && count($pub_place) > 0) {
            $imprint = implode(', ', $pub_place);
            $imprint .= ': ';
        }
        $publishers = $this->getPublishers();
        if (is_array($publishers) && count($publishers) > 0) {
            $imprint .= implode(', ', $publishers);
            $imprint .= ', ';
        }
        $pub_date = $this->getPublicationDates();
        if (is_array($pub_date)) {
            $imprint .= implode('-', $pub_date);
        }

        return $imprint;
    }

    /**
     * @return  string
     */
    public function getAllSubjectHeadingsAsString()
    {
        $ret = array();
        $subj = $this->getAllSubjectHeadings();
        if (is_array($subj) and count($subj) > 0) {
            foreach ($subj as $sub) {
                $ret = array_merge($ret, $sub);
            }
            $ret = trim(implode('; ', $ret));
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getDatabaseTitle()
    {
        $ret = '';
        $db = $this->getField('DatabaseTitle');
        if (is_array($db)) {
            $ret = implode('; ', $db);
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getAltTitle()
    {
        return '';
    }

    /**
     * @override
     * @return array Strings representing citation formats.
     */
    public function getCitationFormats()
    {
        $solrDefaultAdapter = $this->hierarchyDriverManager->getServiceLocator()->get('Swissbib\RecordDriver\SolrDefaultAdapter');

        return $solrDefaultAdapter->getCitationFormats();
    }

    /**
     * Get structured subject vocabularies from predefined fields
     * Extended version of getAllSubjectHeadings()
     *
     * $fieldIndexes contains keys of fields to check
     * $vocabConfigs contains checks for vocabulary detection
     *
     * $vocabConfigs:
     * - ind: Value for indicator 2 in tag
     * - field: sub field 2 in tag
     * - fieldsOnly: Only check for given field indexes
     * - detect: The vocabulary key is defined in sub field 2.
     *      Don't use the key in the config (only used for local)
     *
     * Expected result:
     * [
     *        gnd => [
     *            600 => [{},{},{},...]
     *            610 => [{},{},{},...]
     *            620 => [{},{},{},...]
     *        ],
     *    rero => [
     *            600 => [{},{},{},...]
     *            610 => [{},{},{},...]
     *            620 => [{},{},{},...]
     *        ]
     * ]
     * {} is an assoc array which contains the field data
     *
     * @param boolean $ignoreControlFields Ignore control fields 0 and 2
     *
     * @return array
     */
    public function getAllSubjectVocabularies($ignoreControlFields = false)
    {
        return array();
    }

    /**
     * Get raw formats as provided by the basic driver
     * Wrap for getFormats() because it's overwritten in this driver
     *
     * @return string[]
     */
    public function getFormatsRaw()
    {
        // TODO: Implement getFormatsRaw() method.
    }

    /**
     * get Cartographic Mathematical Data
     *
     * @return string
     */
    public function getCartMathData()
    {
        // TODO: Implement getCartMathData() method.
    }

    /**
     * get group-id from solr-field to display FRBR-Button
     *
     * @return string|number
     */
    public function getGroup()
    {
        // TODO: Implement getGroup() method.
    }

    /**
     * Get host item entry
     *
     * @return array
     */
    public function getHostItemEntry()
    {
        // TODO: Implement getHostItemEntry() method.
    }

    /**
     * Get unions
     *
     * @return string
     */
    public function getUnions()
    {
        // TODO: Implement getUnions() method.
    }

    /**
     * Get online status
     *
     * @return boolean
     */
    public function getOnlineStatus()
    {
        // TODO: Implement getOnlineStatus() method.
    }

    /**
     * Returns the corporation names
     *
     * @param boolean $asString
     * @return  array|string
     */
    public function getCorporationNames($asString = true)
    {
        return $asString ? '' : array();
    }

    /**
     * @return boolean
     */
    public function displayHoldings()
    {
        return false;
    }

    /**
     * @param string $size
     * @return array|bool|string
     */
    public function getThumbnail($size) {
        return parent::getThumbnail('small');
    }

    /**
     * @param    String $fieldName
     * @param    String $fallbackValue
     *
     * @return    String
     */
    private function getField($fieldName, $fallbackValue = '')
    {
        return array_key_exists($fieldName, $this->fields) ? $this->fields[$fieldName] : $fallbackValue;
    }

    /**
     * @return    Array
     */
    private function getLinkModel()
    {
        return $this->getField('LinkModel');
    }
}
