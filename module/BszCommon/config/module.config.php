<?php

use BszCommon\Controller\AbstractBaseFactory;

$config = [
    'controllers' => [
        'abstract_factories' => [
            AbstractBaseFactory::class
        ]
    ],
    'vufind' => [
        'plugin_managers' => [
            'autocomplete' => [
                'abstract_factories' => [
                    \BszCommon\Autocomplete\AbstractSearchNCNFactory::class
                ]
            ],
            'search_options' => [
                'abstract_factories' => [
                    \BszCommon\Search\SearchN\OptionsFactory::class
                ]
            ],
            'search_params' => [
                'abstract_factories' => [
                    \BszCommon\Search\SearchN\ParamsFactory::class
                ]
            ],
            'search_results' => [
                'abstract_factories' => [
                    \BszCommon\Search\SearchN\ResultsFactory::class
                ]
            ],
            'search_facetcache' => [
              'abstract_factories' => [
                  \BszCommon\Search\SearchN\FacetCacheFactory::class
              ]
            ],
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
