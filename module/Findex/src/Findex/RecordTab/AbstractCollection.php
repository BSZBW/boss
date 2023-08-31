<?php

namespace Findex\RecordTab;

use VuFind\RecordTab\AbstractBase;
use VuFindSearch\ParamBag;
use VuFindSearch\Service as SearchService;

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

    public function isActive()
    {
        return parent::isActive() && (count($this->getResults()) > 0);
    }

}