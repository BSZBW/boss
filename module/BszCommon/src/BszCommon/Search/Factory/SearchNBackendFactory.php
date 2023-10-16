<?php

namespace BszCommon\Search\Factory;

use VuFind\Search\Factory\SolrDefaultBackendFactory;

class SearchNBackendFactory extends SolrDefaultBackendFactory
{
    public function __construct(String $searchId)
    {
        parent::__construct();
        $this->createRecordMethod = 'getSolrRecord';
        $this->mainConfig = $this->searchConfig = $this->facetConfig = $searchId;
        $this->searchYaml = 'searchspecs' . str_replace('search', '', strtolower($searchId)) . '.yaml';
    }
}