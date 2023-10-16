<?php

namespace BszCommon\Search\SearchN;

use BszCommon\AbstractSearchNFactory;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class OptionsFactory extends AbstractSearchNFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        return new Options($container->get(\VuFind\Config\PluginManager::class), $requestedName);
    }

}