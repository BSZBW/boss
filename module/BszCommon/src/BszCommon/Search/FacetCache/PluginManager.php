<?php

namespace BszCommon\Search\FacetCache;

class PluginManager extends \VuFind\Search\FacetCache\PluginManager
{

    public function __construct($configOrContainerInstance = null, array $v3config = [])
    {
        $this->addAbstractFactory(\BszCommon\Search\SearchN\AbstractFacetCacheFactory::class);
        parent::__construct($configOrContainerInstance, $v3config);
    }

}