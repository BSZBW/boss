<?php

namespace BszCommon\Search\SearchN;

use Bsz\Exception;
use Interop\Container\ContainerInterface;

class AbstractFacetCacheFactory extends \BszCommon\AbstractSearchNFactory
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if(!empty($options)){
            throw new Exception('Unexpected options sent to factory.');
        }

        $filters = $container->get(\VuFind\Search\SearchTabsHelper::class)
            ->getHiddenFilters($requestedName);
        $results = $container->get(\VuFind\Search\Results\PluginManager::class)
            ->get($requestedName);
        $params = $results->getParams();
        foreach ($filters as $key => $subFilters) {
            foreach ($subFilters as $filter) {
                $params->addHiddenFilter("$key:$filter");
            }
        }

        $cacheManager = $container->get(\VuFind\Cache\Manager::class);
        $language = $container->get(\Zend\Mvc\I18n\Translator::class)->getLocale();
        return new FacetCache($results, $cacheManager, $requestedName, $language);
    }
}