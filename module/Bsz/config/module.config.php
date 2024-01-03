<?php
/*
 * Copyright 2020 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

namespace Bsz\Module\Config;

use Bsz\Auth\ShibbolethFactory;
use Bsz\Controller\Factory;
use Bsz\Auth\Factory as AuthFactory;
use Bsz\Route\RouteGenerator;

$config = [

    'controllers' => [
        'factories' => [
            'Bsz\Controller\SearchController' => Factory::class,
            'Bsz\Controller\RecordController' => 'Bsz\Controller\Factory::getRecordController',
            'Bsz\Controller\Search2RecordController' => 'Bsz\Controller\Factory::getSearch2RecordController',
            'Bsz\Controller\EdsrecordController' => Factory::class,
            'Bsz\Controller\MyResearchController' => Factory::class,
            'Bsz\Controller\HoldingController' => Factory::class,
            'Bsz\Controller\ShibController' => Factory::class,
            'Bsz\Controller\BszController' => Factory::class,
            'Bsz\Controller\CoverController' => 'Bsz\Controller\Factory::getCoverController',
        ],
        'aliases' => [
            // shortcuts for our own controllers
            'Holding' => 'Bsz\Controller\HoldingController',
            'Shib' => 'Bsz\Controller\ShibController',
            'Bsz' => 'Bsz\Controller\BszController',
            // overwriting
            'VuFind\Controller\SearchController'    => 'Bsz\Controller\SearchController',
            'VuFind\Controller\RecordController'    => 'Bsz\Controller\RecordController',
            'VuFind\Controller\EdsrecordController'    => 'Bsz\Controller\EdsrecordController',
            'VuFind\Controller\MyResearchController'   => 'Bsz\Controller\MyResearchController',
            'VuFind\Controller\CoverController' => 'Bsz\Controller\CoverController',
            'VuFind\Controller\Search2recordController' => 'Bsz\Controller\Search2RecordController'
        ]
    ],
    'router' => [
        'routes' => [
            'saveisil'=> [
                'type'    => 'Segment',
                'options' => [
                    'route'    => "/Bsz/saveIsil/:isil",
                    'constraints' => [
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'isil'       => 'DE-[a-zA-Z0-9\-\/,]+'
                    ],
                    'defaults' => [
                        'controller' => 'Bsz',
                        'action'     => 'saveIsil',
                    ]
                ]

            ]
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Bsz\Config\Client'     => 'Bsz\Config\Factory::getClient',
            'Bsz\Config\Libraries'  => 'Bsz\Config\Factory::getLibrariesTable',
            'Bsz\Config\Dedup'  => 'Bsz\Config\Factory::getDedup',
            'LibrariesTableGateway' => 'Bsz\Config\Factory::getLibrariesTableGateway',
            'PlacesTableGateway' => 'Bsz\Config\Factory::getPlacesTableGateway',
            'Bsz\ILL\Holding'    => 'Bsz\ILL\Factory::getHolding',
            'Bsz\Parser\OpenUrl' => 'Bsz\Parser\Factory::getOpenUrlParser',
            'Bsz\SearchTabsHelper' => 'Bsz\Service\Factory::getSearchTabsHelper',
            'Bsz\Auth\Manager' => 'Bsz\Auth\Factory::getManager',
            'Bsz\Auth\PluginManager' => 'VuFind\ServiceManager\AbstractPluginManagerFactory',
            'Bsz\RecordDriver\PluginManager' => 'Bsz\RecordDriver\PluginManagerFactory',
            'Bsz\ILL\Logic' => 'Bsz\ILL\Factory::getIllLogic',

        ],
        'invokables' => [
            'Bsz\RecordDriver\Constants' => 'Bsz\RecordDriver\Constants',
            'Bsz\Config\Library'    => 'Bsz\Config\Library',
        ],
        'aliases' => [
            'VuFind\SearchTabsHelper'   => 'Bsz\SearchTabsHelper',
            'VuFind\Auth\Manager'           => 'Bsz\Auth\Manager',
            'VuFind\RecordDriver\PluginManager' => 'Bsz\RecordDriver\PluginManager',
            'VuFind\Auth\PluginManager' => 'Bsz\Auth\PluginManager'

        ]
    ],
    'view_manager' => [
        'display_exceptions'       => APPLICATION_ENV == 'development',
    ],

    'vufind' => [
        'plugin_managers' => [
            'auth' => [
                'factories' => [
                   'Bsz\Auth\Shibboleth' => ShibbolethFactory::class,
                   'Bsz\Auth\Koha' => AuthFactory::class,
                ],
                'aliases' => [
                    'VuFind\Auth\Shibboleth' => 'Bsz\Auth\Shibboleth'
                ]
            ],
            'recommend' => [
                'factories' => [
                    'Bsz\Recommend\SideFacets' => 'Bsz\Recommend\Factory::getSideFacets',
                    'Bsz\Recommend\SearchButtons' => 'Bsz\Recommend\Factory::getSearchButtons',
                    'Bsz\Recommend\RSSFeedResults' => 'Bsz\Recommend\Factory::getRSSFeedResults',
                    'Bsz\Recommend\StartPageNews' => 'Bsz\Recommend\Factory::getStartpageNews',
                ],
                'invokables' => [
                    'Bsz\Recommend\RSSFeedResultsDeferred' => 'Bsz\Recommend\RSSFeedResultsDeferred',
                ],
                'aliases' => [
                    'VuFind\Recommend\SideFacets' => 'Bsz\Recommend\SideFacets',
                    'StartPageNews' => 'Bsz\Recommend\StartPageNews',
                    'SearchButtons' => 'Bsz\Recommend\SearchButtons',
                    'RSSFeedResults' => 'Bsz\Recommend\RSSFeedResults',
                    'RSSFeedResultsDeferred' => 'Bsz\Recommend\RSSFeedResultsDeferred',
                ]
            ],
            'recorddriver'  => [
                'factories' => [
                    'Bsz\RecordDriver\SolrMarc'         => 'Bsz\RecordDriver\Factory',
                    'Bsz\RecordDriver\SolrGviMarc'      => 'Bsz\RecordDriver\Factory',
                     'Bsz\RecordDriver\SolrGviMarcDE101' => 'Bsz\RecordDriver\Factory',
                    'Bsz\RecordDriver\SolrGviMarcDE576' => 'Bsz\RecordDriver\Factory',
                    'Bsz\RecordDriver\SolrGviMarcDE600' => 'Bsz\RecordDriver\Factory',
                    'Bsz\RecordDriver\SolrGviMarcDE601' => 'Bsz\RecordDriver\Factory',
                    'Bsz\RecordDriver\SolrGviMarcDE602' => 'Bsz\RecordDriver\Factory',
                    'Bsz\RecordDriver\SolrGviMarcDE603' => 'Bsz\RecordDriver\Factory',
                    'Bsz\RecordDriver\SolrGviMarcDE604' => 'Bsz\RecordDriver\Factory',
                    'Bsz\RecordDriver\SolrGviMarcDE605' => 'Bsz\RecordDriver\Factory',
                    'Bsz\RecordDriver\SolrGviMarcDE627' => 'Bsz\RecordDriver\Factory',
                    'Bsz\RecordDriver\SolrGviMarcATOBV' => 'Bsz\RecordDriver\Factory',
                    'Bsz\RecordDriver\EDS'              => 'Bsz\RecordDriver\Factory::getEDS',
                    'Bsz\RecordDriver\Summon'              => 'Bsz\RecordDriver\Factory::getSummon',

                ],
                'aliases' => [
                    'SolrGviMarc'      =>  'Bsz\RecordDriver\SolrGviMarc',
                    'SolrGvimarc'      =>  'Bsz\RecordDriver\SolrGviMarc',
                    'SolrGviMarcDE101' =>  'Bsz\RecordDriver\SolrGviMarcDE101',
                    'SolrGviMarcDE576' =>  'Bsz\RecordDriver\SolrGviMarcDE576',
                    'SolrGviMarcDE600' =>  'Bsz\RecordDriver\SolrGviMarcDE600',
                    'SolrGviMarcDE601' =>  'Bsz\RecordDriver\SolrGviMarcDE601',
                    'SolrGviMarcDE602' =>  'Bsz\RecordDriver\SolrGviMarcDE602',
                    'SolrGviMarcDE603' =>  'Bsz\RecordDriver\SolrGviMarcDE603',
                    'SolrGviMarcDE604' =>  'Bsz\RecordDriver\SolrGviMarcDE604',
                    'SolrGviMarcDE605' =>  'Bsz\RecordDriver\SolrGviMarcDE605',
                    'SolrGviMarcDE627' =>  'Bsz\RecordDriver\SolrGviMarcDE627',
                    'SolrGviMarcATOBV' =>  'Bsz\RecordDriver\SolrGviMarcATOBV',
                    'VuFind\RecordDriver\SolrMarc'  => 'Bsz\RecordDriver\SolrMarc',
                    'VuFind\RecordDriver\EDS'       => 'Bsz\RecordDriver\EDS',
                    'VuFind\RecordDriver\Summon'       => 'Bsz\RecordDriver\Summon',
                ],
                'delegators' => [
                    'Bsz\RecordDriver\SolrMarc'        => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriver\SolrGviMarc'      => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriver\SolrGviMarcDE627'=> [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriver\SolrGviMarcDE101' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriver\SolrGviMarcDE576' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriver\SolrGviMarcDE600' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriver\SolrGviMarcDE601' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriver\SolrGviMarcDE602' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriver\SolrGviMarcDE603' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriver\SolrGviMarcDE604' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriver\SolrGviMarcDE605' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                    'Bsz\RecordDriver\SolrGviMarcATOBV' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                ]
            ],
            'recordtab' => [
                'factories' => [
                    'Bsz\RecordTab\HoldingsILS' => 'Bsz\RecordTab\Factory::getHoldingsILS',
                    'Bsz\RecordTab\Volumes' => 'Bsz\RecordTab\Factory::getVolumes',
                    'Bsz\RecordTab\Articles' => 'Bsz\RecordTab\Factory::getArticles',
                    'Bsz\RecordTab\Notes' => 'Bsz\RecordTab\Factory::getNotes',
                    'Bsz\RecordTab\Libraries' => 'Bsz\RecordTab\Factory::getLibraries',
                    'Bsz\RecordTab\InterlibraryLoan' => 'Bsz\RecordTab\Factory::getInterLibraryLoan',
                    'Bsz\RecordTab\StaffViewAll' => 'InvokableFactory::class',

                ],
                'aliases' => [
                    'VuFind\RecordTab\HoldingsILS' => 'Bsz\RecordTab\HoldingsILS',
                    'Articles' => 'Bsz\RecordTab\Articles',
                    'Volumes' => 'Bsz\RecordTab\Volumes',
                    'Notes' => 'Bsz\RecordTab\Notes',
                    'Libraries' => 'Bsz\RecordTab\Libraries',
                    'InterlibraryLoan' => 'Bsz\RecordTab\InterlibraryLoan',
                    'staffviewall' => 'Bsz\RecordTab\StaffViewAll',
                ]
            ],
            'search_backend' => [
                'factories' => [
                    'Solr' => 'Bsz\Search\Factory\SolrDefaultBackendFactory',
                    'EDS' => 'Bsz\Search\Factory\EdsBackendFactory',
                ],
            ],
            'search_params'  => [
                'factories' => [
                    'Bsz\Search\Solr\Params' =>     'Bsz\Search\Params\Factory',
                    'Bsz\Search\Search2\Params' =>  'Bsz\Search\Params\Factory'
                ],
                'aliases' => [
                    'VuFind\Search\Solr\Params' =>      'Bsz\Search\Solr\Params',
                    'VuFind\Search\Search2\Params' =>   'Bsz\Search\Search2\Params',
                ]
            ],
            'ils_driver' => [
                'factories' => [
                    'Bsz\ILS\Driver\DAIAbsz' => 'Bsz\ILS\Driver\DAIAFactory',
                    'Bsz\ILS\Driver\DAIA' => 'Bsz\ILS\Driver\DAIAFactory',
                    'Bsz\ILS\Driver\NoILS' => 'Bsz\ILS\Driver\NoILSFactory',
                ],
                'aliases' => [
                    'DAIAbsz' => 'Bsz\ILS\Driver\DAIAbsz',
                    'VuFind\ILS\Driver\DAIA' => 'Bsz\ILS\Driver\DAIA',
                    'VuFind\ILS\Driver\NoILS' => 'Bsz\ILS\Driver\NoILS',
                ]

            ],
            'ajaxhandler' => [
                'factories' => [
                    'Bsz\AjaxHandler\DedupCheckbox' =>      'Bsz\AjaxHandler\Factory::getDedupCheckbox',
                    'Bsz\AjaxHandler\SaveIsil' =>           'Bsz\AjaxHandler\Factory::getSaveIsil',
                    'Bsz\AjaxHandler\LibrariesTypeahead' => 'Bsz\AjaxHandler\Factory::getLibrariesTypeahead',

                ],
                'aliases' => [
                    'dedupCheckbox' => 'Bsz\AjaxHandler\DedupCheckbox',
                    'saveIsil' => 'Bsz\AjaxHandler\SaveIsil',
                    'librariesTypeahead' => 'Bsz\AjaxHandler\LibrariesTypeahead'
                ]
            ],
            'resolver_driver' => [
                'factories' => [
                    'Bsz\Resolver\Driver\Ezb' => 'Bsz\Resolver\Driver\Factory::getEzb',
                    'Bsz\Resolver\Driver\Redi' => 'Bsz\Resolver\Driver\Factory::getRedi',
                    'Bsz\Resolver\Driver\Ill' => 'Bsz\Resolver\Driver\Factory::getIll',
                ],
                'aliases' => [
                    'VuFind\Resolver\Driver\Redi' => 'Bsz\Resolver\Driver\Redi',
                    'VuFind\Resolver\Driver\Ezb'  => 'Bsz\Resolver\Driver\Ezb',
                    'ill' => 'Bsz\Resolver\Driver\Ill'
                ]
            ],

        ],
    ]

];
$staticRoutes = [
    'Test/Record', 'Test/phpinfo', 'Test/zfl',
    'Bsz/index', 'Bsz/library',
    'Record/Freeform',
    'Holding/Query',
    'Bsz/Privacy',
    'Bsz/Dedup',
    'Bsz/Resigning',
    'Shib/Wayf', 'Shib/Redirect',
];
$recordRoutes = [
    'record' => 'Record',
    'search2record'=> 'Search2Record'
];

$routeGenerator = new RouteGenerator();
$routeGenerator->addRecordRoutes($config, $recordRoutes);
//$routeGenerator->addDynamicRoutes($config, $dynamicRoutes);
$routeGenerator->addStaticRoutes($config, $staticRoutes);
return $config;
