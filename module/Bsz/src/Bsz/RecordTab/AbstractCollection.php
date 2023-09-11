<?php

namespace Bsz\RecordTab;

use VuFind\RecordTab\AbstractBase;
use VuFindSearch\ParamBag;
use VuFindSearch\Query\Query;
use VuFindSearch\Service as SearchService;

abstract class AbstractCollection extends \BszCommon\RecordTab\AbstractCollection
{

    /**
     * @var array
     */
    protected $isils;

    public function __construct(SearchService $search, array $isils)
    {
        parent::__construct($search);
        $this->isils = $isils;
    }

    protected function getQuery(): Query
    {
        $id = $this->driver->getUniqueID();
        return new Query('id_related:"' . $id . '"');
    }

    protected function getParams(): ParamBag
    {
        $filterOr = [];
        if ($this->isInterlending() === false) {
            foreach ($this->isils as $isil) {
                $filterOr[] = 'institution_id:' . $isil;
            }
        }
        $params = new ParamBag();
        $params->add('fq', implode(' OR ', $filterOr));

        //$params->add('sort', 'publish_date_sort desc');
        $params->add('hl', 'false');
        $params->add('echoParams', 'ALL');

        return $params;
    }


    /**
     * Check if we are in an interlending or ZDB-TAB
     *
     * @return bool
     * **/
    private function isInterlending(): bool
    {
        $last = '';
        if (isset($_SESSION['Search']['last'])) {
            $last = urldecode($_SESSION['Search']['last']);
        }
        if (strpos($last, 'consortium:FL') !== false
            || strpos($last, 'consortium:"FL"') !== false
            || strpos($last, 'consortium:ZDB') !== false
            || strpos($last, 'consortium:"ZDB"') !== false
        ) {
            return true;
        } else {
            return false;
        }
    }

}