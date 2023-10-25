<?php

namespace BszCommon\Search\SearchN;

use VuFind\Route\RouteGenerator;

class SearchGenerator
{
    public function addSearch(&$config, $searchName)
    {
        $searchClassId = ucfirst($searchName);

        $config['controllers']['factories'][$searchClassId]
            = Factories::getSearchControllerFactory($searchClassId);
        $config['controllers']['factories'][$searchClassId . 'Record']
            = Factories::getRecordControllerFactory($searchClassId);
        $config['controllers']['factories'][$searchClassId . 'Collection']
            = Factories::getCollectionControllerFactory($searchClassId);
        $config['vufind']['plugin_managers']['autocomplete']['factories'][$searchClassId]
            = Factories::getSearchNCNFactory($searchClassId);
        $config['vufind']['plugin_managers']['search_options']['factories'][$searchClassId]
            = Factories::getOptionsFactory($searchClassId);
        $config['vufind']['plugin_managers']['search_params']['factories'][$searchClassId]
            = Factories::getParamsFactory($searchClassId);
        $config['vufind']['plugin_managers']['search_results']['factories'][$searchClassId]
            = Factories::getResultsFactory($searchClassId);
        $config['vufind']['plugin_managers']['search_facetcache']['factories'][$searchClassId]
            = Factories::getFacetCacheFactory($searchClassId);
        $config['vufind']['plugin_managers']['search_backend']['factories'][$searchClassId]
            = Factories::getBackendFactory($searchClassId);

        $routeGenerator = new RouteGenerator();
        $routeGenerator->addRecordRoutes($config, [
            strtolower($searchClassId) . 'record' => $searchClassId . 'Record',
            strtolower($searchClassId) . 'collection' => $searchClassId
                . 'Collection',
            strtolower($searchClassId) . 'collectionrecord' => $searchClassId
                . 'Record'
        ]);
        $routeGenerator->addStaticRoutes($config, [
            $searchClassId . '/Advanced',
            $searchClassId . '/FacetList',
            $searchClassId . '/Home',
            $searchClassId . '/Results'
        ]);
    }
}
