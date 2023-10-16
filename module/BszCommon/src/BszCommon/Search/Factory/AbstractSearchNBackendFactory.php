<?php

namespace BszCommon\Search\Factory;

use BszCommon\AbstractSearchNFactory;
use Interop\Container\ContainerInterface;

class AbstractSearchNBackendFactory extends AbstractSearchNFactory
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $name = str_replace('Collection', '', $requestedName);
        $factory = new SearchNBackendFactory($name);
        return $factory->__invoke($container, $name, $options);
    }

}