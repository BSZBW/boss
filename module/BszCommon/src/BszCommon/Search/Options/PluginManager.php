<?php

namespace BszCommon\Search\Options;

class PluginManager extends \VuFind\Search\Options\PluginManager
{
    public function __construct($configOrContainerInstance = null, array $v3config = [])
    {
        $this->addAbstractFactory(\BszCommon\Search\SearchN\OptionsFactory::class);
        parent::__construct($configOrContainerInstance, $v3config);
    }

//    protected $abstractFactories = [
//        \BszCommon\Search\SearchN\OptionsFactory::class
//    ];

}