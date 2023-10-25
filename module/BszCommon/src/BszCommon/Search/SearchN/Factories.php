<?php

namespace BszCommon\Search\SearchN;

use BszCommon\Autocomplete\SearchNCN;
use BszCommon\Controller\SearchNCollectionController;
use BszCommon\Controller\SearchNController;
use BszCommon\Controller\SearchNRecordController;
use BszCommon\Search\Factory\SearchNBackendFactory;
use Interop\Container\ContainerInterface;
use VuFind\Search\Factory\UrlQueryHelperFactory;

class Factories
{
    public static function getBackendFactory(string $searchClassId): callable
    {
        return function (
            ContainerInterface $container,
            string $requestedName
        ) use ($searchClassId) {
            $factory = new SearchNBackendFactory($searchClassId);
            return $factory->__invoke($container, $searchClassId);
        };
    }

    public static function getSearchNCNFactory(string $searchClassId): callable
    {
        return function (
            ContainerInterface $container,
            string $requestedName
        ) use ($searchClassId) {
            return new SearchNCN(
                $container->get(\VuFind\Search\Results\PluginManager::class),
                $searchClassId
            );
        };
    }

    public static function getFacetCacheFactory(string $searchClassId): callable
    {
        return function (
            ContainerInterface $container,
            string $requestedName
        ) use ($searchClassId) {
            $filters = $container->get(\VuFind\Search\SearchTabsHelper::class)
                ->getHiddenFilters($searchClassId);
            $results = $container->get(
                \VuFind\Search\Results\PluginManager::class
            )->get($searchClassId);
            $params = $results->getParams();
            foreach ($filters as $key => $subFilters) {
                foreach ($subFilters as $filter) {
                    $params->addHiddenFilter("$key:$filter");
                }
            }

            $cacheManager = $container->get(\VuFind\Cache\Manager::class);
            $language = $container->get(\Zend\Mvc\I18n\Translator::class)
                ->getLocale();
            return new FacetCache(
                $results,
                $cacheManager,
                $searchClassId,
                $language
            );
        };
    }

    public static function getOptionsFactory(string $searchClassId): callable
    {
        return function (
            ContainerInterface $container,
            string $requestedName
        ) use ($searchClassId) {
            return new Options(
                $container->get(\VuFind\Config\PluginManager::class),
                $searchClassId
            );
        };
    }

    public static function getParamsFactory(string $searchClassId): callable
    {
        return function (
            ContainerInterface $container,
            string $requestedName
        ) use ($searchClassId) {
            $optionsObj = $container->get(
                \VuFind\Search\Options\PluginManager::class
            )->get($searchClassId);
            $configLoader = $container->get(
                \VuFind\Config\PluginManager::class
            );
            return new Params(clone $optionsObj, $configLoader);
        };
    }

    public static function getResultsFactory(string $searchClassId): callable
    {
        return function (
            ContainerInterface $container,
            string $requestedName
        ) use ($searchClassId) {
            $params = $container->get(
                \VuFind\Search\Params\PluginManager::class
            )
                ->get($searchClassId);
            $searchService = $container->get(\VuFindSearch\Service::class);
            $recordLoader = $container->get(\VuFind\Record\Loader::class);
            $results = new Results(
                $params,
                $searchService,
                $recordLoader,
                $searchClassId
            );
            $results->setUrlQueryHelperFactory(
                $container->get(UrlQueryHelperFactory::class)
            );
            return $results;
        };
    }

    public static function getRecordControllerFactory(
        string $searchClassId
    ): callable {
        return function (
            ContainerInterface $container,
            string $requestedName
        ) use ($searchClassId) {
            return new SearchNRecordController($container, $searchClassId);
        };
    }

    public static function getSearchControllerFactory(
        string $searchClassId
    ): callable {
        return function (
            ContainerInterface $container,
            string $requestedName
        ) use ($searchClassId) {
            return new SearchNController($container, $searchClassId);
        };
    }

    public static function getCollectionControllerFactory(
        string $searchClassId
    ): callable {
        return function (
            ContainerInterface $container,
            string $requestedName
        ) use ($searchClassId) {
            $config = $container->get(\VuFind\Config\PluginManager::class)
                ->get('config');
            return new SearchNCollectionController(
                $container,
                $config,
                $searchClassId
            );
        };
    }
}
