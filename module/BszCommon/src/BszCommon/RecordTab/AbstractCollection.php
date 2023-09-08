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

namespace BszCommon\RecordTab;

use VuFind\RecordTab\AbstractBase;
use VuFindSearch\ParamBag;
use VuFindSearch\Query\Query;
use VuFindSearch\Service as SearchService;

/**
 * This class provides an abstract description for a RecordTab that displays
 * titles dependent of the current record (e.g. a list of articles if the
 * current record represents a serial).
 */
abstract class AbstractCollection extends AbstractBase
{
    /**
     * @var int
     */
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
     * Constructor
     *
     * @param SearchService $search
     */
    public function __construct(SearchService $search)
    {
        $this->searchService = $search;
    }

    /**
     * Returns a list of all records that should be displayed in this tab.
     *
     * @return array
     * @throws \Exception
     */
    public function getResults()
    {
        if ($this->results === null) {
            $query = $this->getQuery();
            $params = $this->getParams();

            $records = $this->searchService->search(
                $this->getRecordDriver()->getSourceIdentifier(),
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
     * Returns the Solr-query to retrieve all the dependent titles of the current record
     * @return Query
     */
    protected abstract function getQuery(): Query;

    /**
     * Returns additional parameters for the search query
     *
     * @return ParamBag
     */
    protected abstract function getParams(): ParamBag;

    /**
     * Indicates whether a given record (dependent title) should be displayed in this tab
     * @param $record
     * @return bool
     */
    abstract protected function display($record): bool;

    /**
     * @return bool
     * @throws \Exception
     */
    public function isActive()
    {
        return parent::isActive() && (count($this->getResults()) > 0);
    }


}