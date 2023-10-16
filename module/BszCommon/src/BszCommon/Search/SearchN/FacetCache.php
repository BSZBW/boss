<?php

namespace BszCommon\Search\SearchN;

use VuFind\Cache\Manager as CacheManager;
use VuFind\Search\Base\Results;

class FacetCache extends \VuFind\Search\Base\FacetCache
{
    private $cacheNamespace;

    public function __construct(Results $r, CacheManager $cm, String $searchClassId, $language = 'en')
    {
        parent::__construct($r, $cm, $language);
        $this->cacheNamespace = strtolower($this->cacheNamespace) . '-facets';
    }

    protected function getCacheNamespace()
    {
        return $this->cacheNamespace;
    }
}