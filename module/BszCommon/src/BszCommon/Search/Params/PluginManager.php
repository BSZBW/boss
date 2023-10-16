<?php

namespace BszCommon\Search\Params;

class PluginManager extends \VuFind\Search\Params\PluginManager
{

    public function __construct($configOrContainerInstance = null, array $v3config = [])
    {
        $this->addAbstractFactory(\BszCommon\Search\SearchN\ParamsFactory::class);
        parent::__construct($configOrContainerInstance, $v3config);
    }

//    protected $abstractFactories = [
//        \BszCommon\Search\SearchN\ParamsFactory::class
//    ];

}