<?php
/**
 * Related Records: Solr-based similarity
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2009.
 *
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind2
 * @package  Related_Records
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_a_related_record_module Wiki
 */
namespace Bsz\Related;

/**
 * Extended because of missing institution id in original vufind version
 *
 * @category VuFind2
 * @package  Related_Records
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_a_related_record_module Wiki
 */
class Similar extends \VuFind\Related\Similar
{
    /**
     *
     * @var \Bsz\Config\Client
     */
    protected $client;

    /**
     * Constructor
     *
     * @param \VuFindSearch\Service $search Search service
     */
    public function __construct(\VuFindSearch\Service $search, \Bsz\Config\Client $client)
    {
        $this->searchService = $search;
        $this->client = $client;
    }

    /**
     * Establishes base settings for making recommendations.
     *
     * @param string                            $settings Settings from config.ini
     * @param \VuFind\RecordDriver\AbstractBase $driver   Record driver object
     *
     * @return void
     */
    public function init($settings, $driver)
    {
        $tmp = [
            'mlt.fl' => 'author,title^5,publish_date',
            'mlt.count' => 4,
//            'mlt.maxqt' => 1,
        ];
        if ($this->client && count($this->client->getIsils()) > 0) {
            $filter = [];
            foreach ($this->client->getIsils() as $isil) {
                $filter[] = 'institution_id:' . $isil;
            }

            $tmp['fq'] = implode(' OR ', $filter);
        }
        $params = new \VuFindSearch\ParamBag($tmp);
        $this->results
            = $this->searchService->similar('Solr', $driver->getUniqueId(), $params);
    }

    /**
     * Get an array of Record Driver objects representing items similar to the one
     * passed to the constructor.
     *
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }
}
