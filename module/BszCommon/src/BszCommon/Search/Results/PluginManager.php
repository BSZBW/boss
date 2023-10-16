<?php

namespace BszCommon\Search\Results;

class PluginManager extends \VuFind\Search\Results\PluginManager
{
    public function __construct($configOrContainerInstance = null, array $v3config = [])
    {
        $this->addAbstractFactory(\BszCommon\Search\SearchN\ResultsFactory::class);
        parent::__construct($configOrContainerInstance, $v3config);
    }

//    protected $abstractFactories = [
//        \BszCommon\Search\SearchN\ResultsFactory::class
//    ];

}