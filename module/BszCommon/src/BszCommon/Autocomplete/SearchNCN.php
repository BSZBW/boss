<?php

namespace BszCommon\Autocomplete;

use VuFind\XSLT\Import\VuFind;

class SearchNCN extends \VuFind\Autocomplete\SolrCN
{
    public function __construct(\VuFind\Search\Results\PluginManager $results, $searchClassId)
    {
        parent::__construct($results);
        $this->searchClassId = $searchClassId;
    }
}