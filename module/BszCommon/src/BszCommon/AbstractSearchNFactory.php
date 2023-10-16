<?php

namespace BszCommon;

abstract class AbstractSearchNFactory implements \Zend\ServiceManager\Factory\AbstractFactoryInterface
{
    public function canCreate(\Interop\Container\ContainerInterface $container, $requestedName)
    {
        return preg_match('/search(?:[3-9][0-9]*|[1-9][0-9]+)/', strtolower($requestedName));
    }

}