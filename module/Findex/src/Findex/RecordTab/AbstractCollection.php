<?php

/*
 * Copyright (C) 2023 Bibliotheks-Service Zentrum, Konstanz, Germany
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
use VuFindSearch\Query\Query as SearchQuery;
use VuFindSearch\Service as SearchService;

/**
 * @package Findex\RecordTab
 * @author Sebastian Sahli
 */
abstract class AbstractCollection extends AbstractBase
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
    public function __construct(SearchService $search, $isils)
    {
        $this->searchService = $search;
        $this->isils = $isils;
    }

    public function getResults()
    {
        if ($this->results === null) {
            $id = $this->driver->getUniqueID();
            //hierarchy_parent_id stores the data from MARC fields 773w, 800w, 810w, 830w
            $queryStr = 'hierarchy_parent_id:' . $id;
            $query = new SearchQuery($queryStr);

            // in local tab, we need to filter by isil
            $filterOr = [];
            if ($this->isFL() === false) {
                foreach ($this->isils as $isil) {
                    $filterOr[] = 'collection_details:ISIL_' . $isil;
                }
            }
            $params = new ParamBag();
            $params->add('fq', implode(' OR ', $filterOr));
            $params->add('fq', '-id:' . $id);

            $params->add('sort', 'hierarchy_sort_str desc');
            $params->add('hl', 'false');
            $params->add('echoParams', 'ALL');

            $record = $this->getRecordDriver();
            $records = $this->searchService->search(
                $record->getSourceIdentifier(),
                $query,
                0,
                static::LIMIT,
                $params
            )->getRecords();

            $this->results = [];
            foreach ($records as $r) {
                if($this->display($r)) {
                    $this->results[] = $r;
                }
            }
        }
        return $this->results;
    }

    /**
     * Indicates whether a given record should be displayed in this tab
     * @param $record
     * @return bool
     */
    abstract protected function display($record): bool;

    private function isFL()
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
     * @return bool
     * @throws \Exception
     */
    public function isActive()
    {
        return parent::isActive() && (count($this->getResults()) > 0);
    }

}