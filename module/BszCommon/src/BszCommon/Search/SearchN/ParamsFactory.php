<?php

namespace BszCommon\Search\SearchN;

use BszCommon\AbstractSearchNFactory;
use Interop\Container\ContainerInterface;

class ParamsFactory extends AbstractSearchNFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $optionsObj = $container->get(\VuFind\Search\Options\PluginManager::class)
            ->get($requestedName);
        $configLoader = $container->get(\VuFind\Config\PluginManager::class);
        return new Params(clone $optionsObj, $configLoader);
    }

}