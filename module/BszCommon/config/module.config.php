<?php

use BszCommon\Controller\AbstractBaseFactory;

$config = [
    'controllers' => [
        'abstract_factories' => [
            AbstractBaseFactory::class
        ]
    ],
    'service_manager' => [
        'factories' => [
            'BszCommon\Search\Results\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'BszCommon\Search\Options\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'BszCommon\Search\Params\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'BszCommon\Search\FacetCache\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory'
        ],
        'aliases' => [
            'VuFind\Search\Results\PluginManager' => 'BszCommon\Search\Results\PluginManager',
            'VuFind\Search\Options\PluginManager' => 'BszCommon\Search\Options\PluginManager',
            'VuFind\Search\Params\PluginManager' => 'BszCommon\Search\Params\PluginManager',
            'VuFind\Search\FacetCache\PluginManager' => 'BszCommon\Search\FacetCache\PluginManager',
            'VuFind\Search\BackendManagerFactory' => 'BszCommon\Search\BackendManagerFactory'
        ]
    ],
    'vufind' => [
        'plugin_managers' => [
            'search_backend' => [
                'abstract_factories' => [
                    \BszCommon\Search\Factory\AbstractSearchNBackendFactory::class
                ]
            ]
        ]
    ]
];

$config['router']['routes']['searchn'] = [
    'type' => 'Zend\Router\Http\Segment',
    'options' => [
        'route' => '/:controller[/[:action]]',
        'constraints' => [
            'controller' => 'Search([3-9][0-9]*|[1-9][0-9]+)',
        ],
        'defaults' => [
            'action' => 'home'
        ]
    ]
];

$recordRoutes = [
    'record' => 'Record',
    'collection' => 'Collection',
    'collectionrecord' => 'Record'
];

$staticSearchRoutes = ['Home', 'FacetList', 'Results', 'Advanced'];

$routeGenerator = new BszCommon\Route\RouteGenerator();
$routeGenerator->addSearchRecordRoutes($config, $recordRoutes);
$routeGenerator->addSearchRoutes($config, $staticSearchRoutes);

return $config;
