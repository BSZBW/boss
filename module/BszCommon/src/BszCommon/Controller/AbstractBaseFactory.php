<?php

namespace BszCommon\Controller;

use Bsz\Exception;

class AbstractBaseFactory implements \Zend\ServiceManager\Factory\AbstractFactoryInterface
{
    private $regex = '/(Search(?:[3-9][0-9]*|[1-9][0-9]+))([a-zA-Z]+|)/';

    public function canCreate(\Interop\Container\ContainerInterface $container, $requestedName)
    {
        return preg_match($this->regex, $requestedName);
    }

    public function __invoke(\Interop\Container\ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!empty($options)) {
            throw new Exception('Unexpected options sent to factory.');
        }

        $matches = [];
        if (!preg_match($this->regex, $requestedName, $matches)) {
            throw new Exception('Unexpected name sent to factory');
        }


        $searchClassId = $matches[1];
        if (strtolower($matches[2]) === 'collection') {
            $config = $container->get(\VuFind\Config\PluginManager::class)
                ->get('config');
            return new SearchNCollectionController($container, $config, $searchClassId);
        }
        $baseName = ($matches[2] === 'collectionrecord') ? 'record' : $matches[2];
        $className = __NAMESPACE__ . '\\' . 'SearchN' . ucfirst($baseName) . 'Controller';
        return new $className($container, $searchClassId);
    }

}