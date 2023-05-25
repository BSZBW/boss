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
namespace Bsz\RecordTab;

use Bsz\Search\Solr\Results;
use VuFind\RecordTab\AbstractBase;
use VuFindSearch\ParamBag;
use VuFindSearch\Service as SearchService;

/**
 * Class Volumes
 * @package Bsz\RecordTab
 * @category boss
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Volumes extends AbstractBase
{
    const LIMIT = 50;
    /**
     * Search service
     *
     * @var \VuFindSearch\Service
     */
    protected $searchService;

    /**
     *
     * @var array
     */
    protected $results;

    /**
     * @var string
     */
    protected $searchClassId;

    /**
     * @var array
     */
    protected $isils;

    /**
     * Constructor
     *
     * @param SearchService $search
     * @param array $isils
     */
    public function __construct(SearchService $search, $isils = [])
    {
        $this->searchService = $search;
        $this->isils = $isils;
        $this->accessPermission = 'access.VolumesViewTab';
    }

    /**
     * Get the on-screen description for this tab
     * @return string
     */
    public function getDescription()
    {
        return 'Volumes';
    }

    /**
     *
     * @return array|null
     */
    public function getResults()
    {
        if ($this->results === null) {
            $relIds = $this->driver->tryMethod('getIdsRelated');
            $this->content = [];
            if (is_array($relIds) && count($relIds) > 0) {
                // add the ID of the current record, thats usefull if its a
                // Gesamtaufnahme to find the
                array_push($relIds, $this->driver->getUniqueID());
                $queryArr = [];

                foreach ($relIds as $id) {
                    $queryArr[] = 'id_related:"' . $id . '"';
                }
                $query = new \VuFindSearch\Query\Query(
                    implode(' OR ', $queryArr)
                );

                // in local tab, we need to filter by isil
                $filter = [];
                if ($this->isFL() === false) {
                    foreach ($this->isils as $isil) {
                        $filter[] = '~institution_id:' . $isil;
                    }
                }

                $params = new ParamBag(['filter' => $filter]);
                $params->add('-material_content_type', 'Article');
                $params->add('sort', 'publish_date_sort desc');

                $record = $this->getRecordDriver();
                $this->content = $this->searchService->search(
                    $record->getSourceIdentifier(),
                    $query,
                    0,
                    static::LIMIT,
                    $params
                );
            }
        }
        return $this->content;
    }

    /**
     * Check if we are in an interlending or ZDB-TAB
     **/
    public function isFL()
    {
        $last = '';
        $status = false;
        if (isset($_SESSION['Search']['last'])) {
            $last = urldecode($_SESSION['Search']['last']);
        }
        if (strpos($last, 'consortium:FL') !== false
            || strpos($last, 'consortium:"FL"') !== false
            || strpos($last, 'consortium:ZDB') !== false
            || strpos($last, 'consortium:"ZDB"') !== false
        ) {
            $status = true;
        }
        return $status;
    }

    /**
     * This Tab is Active for collections or parts of collections only.
     * @return boolean
     */
    public function isActive()
    {
        $parent = parent::isActive();
        $record = $this->getRecordDriver();

        if ($parent && (
            count($record->getIdsRelated()) > 0
            || $record->tryMethod('isCollection')
            || $record->tryMethod('isMonographicSerial')
            || $record->tryMethod('isJournal')
        )
        ) {
            return true;
        }
        return false;
    }
}
