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

namespace Findex\RecordTab;

use VuFind\RecordTab\AbstractBase;
use VuFindSearch\ParamBag;
use VuFindSearch\Response\RecordCollectionInterface;
use VuFindSearch\Service as SearchService;

/**
 * Class Volumes
 * @package Bsz\RecordTab
 * @category boss
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class FindexVolumes extends AbstractBase
{
    const LIMIT = 50;
    const PREFIX = '(DE-627)';
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
     * @return RecordCollectionInterface|null
     */
    public function getResults()
    {
        if ($this->results === null) {
            $queryStr = 'hierarchy_parent_id:' . $this->driver->getUniqueID();
            $query = new \VuFindSearch\Query\Query($queryStr);

            // in local tab, we need to filter by isil
            $filterOr = [];
            if ($this->isFL() === false) {
                foreach ($this->isils as $isil) {
                    $filterOr[] = 'collection_details:ISIL_' . $isil;
                }
            }
            $params = new ParamBag();
            $params->add('fq', implode(' OR ', $filterOr));
            //$params->add('fq', 'format:Article OR format:"electronic Article"');
//            $params->add('fq', 'format:"electronic Article"');

            $params->add('sort', 'publishDateSort desc');
            $params->add('hl', 'false');
            $params->add('echoParams', 'ALL');

            $record = $this->getRecordDriver();
            $this->results = $this->searchService->search(
                $record->getSourceIdentifier(),
                $query,
                0,
                static::LIMIT,
                $params
            );
        }
        return $this->results;
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
        return $parent && ($record->tryMethod('isCollection')
              || $record->tryMethod('isMonographicSerial')
            ) && ($this->getResults()->getTotal() > 0);
    }

}