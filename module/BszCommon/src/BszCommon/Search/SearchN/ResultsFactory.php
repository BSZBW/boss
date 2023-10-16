<?php

namespace BszCommon\Search\SearchN;

use Bsz\Exception;
use BszCommon\AbstractSearchNFactory;
use Interop\Container\ContainerInterface;
use VuFind\Search\Factory\UrlQueryHelperFactory;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class ResultsFactory extends AbstractSearchNFactory
{

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if(!empty($options)) {
            throw new Exception('Unexpected options sent to factory.');
        }

        $params = $container->get(\VuFind\Search\Params\PluginManager::class)
            ->get($requestedName);
        $searchService = $container->get(\VuFindSearch\Service::class);
        $recordLoader = $container->get(\VuFind\Record\Loader::class);
        $results = new Results($params, $searchService, $recordLoader, $requestedName);
        $results->setUrlQueryHelperFactory(
            $container->get(UrlQueryHelperFactory::class)
        );
        return $results;
    }

}