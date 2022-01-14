<?php
namespace BszConsole\Module\Configuration;

use BszConsole\Controller\Factory;

$config = [
    'controllers' => [
        'factories' => [
            'BszConsole\Controller\CacheController' => Factory::class,

        ],
        'aliases' => [
            'cache' => 'BszConsole\Controller\CacheController',
        ],
    ],
    'console' => [
        'router'  => [
            'routes'  => [
                'default-route' => [
                    'type' => 'catchall',
                    'options' => [
                        'route' => '',
                        'defaults' => [
                            'controller' => 'redirect',
                            'action' => 'consoledefault',
                        ],
                    ],
                ],
            ],
        ],
    ],
//    'service_manager' => [
//        'factories' => [
//            'VuFindConsole\Generator\GeneratorTools' => 'VuFindConsole\Generator\GeneratorToolsFactory',
//        ],
//    ],
    'view_manager' => [
        // CLI tools are admin-oriented, so we should always output full errors:
        'display_exceptions' => true,
    ],
];

$routes = [
    //'compile/less' => 'compile less [--force] [<source>] [<target>]',
    'cache/clean'  => 'cache clean'
];
$routeGenerator = new \VuFindConsole\Route\RouteGenerator();
$routeGenerator->addRoutes($config, $routes);
return $config;
