<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
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
 */
namespace BszCommon\Route;

/**
 * Description of RouteGenerator
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class RouteGenerator extends \VuFind\Route\RouteGenerator
{
    /**
     * Constructor
     *
     * @param array $nonTabRecordActions List of non-tab record actions (null
     * for default).
     */
    public function __construct(array $nonTabRecordActions = null)
    {
        if (null === $nonTabRecordActions) {
            $this->nonTabRecordActions = [
                'AddComment', 'DeleteComment', 'AddTag', 'DeleteTag', 'Save',
                'Email', 'SMS', 'Cite', 'Export', 'RDF', 'Hold', 'BlockedHold',
                'Home', 'StorageRetrievalRequest', 'AjaxTab',
                'BlockedStorageRetrievalRequest', 'ILLRequest', 'BlockedILLRequest',
                'PDF', 'ILLForm'
            ];
        } else {
            $this->nonTabRecordActions = $nonTabRecordActions;
        }
    }

    public function addSearchRecordRoutes(& $config, $routes)
    {
        foreach ($routes as $baseName => $baseController) {
            $this->addSearchRecordRoute($config, $baseName, $baseController);
        }
    }

    public function addSearchRecordRoute(& $config, $baseName, $baseController) {
        $routeName = 'searchn' . strtolower($baseName);
        $controllerSpec = 'Search([3-9][0-9]*|[1-9][0-9]+)' . $baseController;

        $config['router']['routes'][$routeName] = [
            'type' => 'Zend\Router\Http\Segment',
            'options' => [
                'route' => '/:controller[/:id[/[:tab]]]',
                'constraints' => [
                    'controller' => $controllerSpec,
                    'action'     => '[a-zA-Z][a-zA-Z0-9_-]*'
                ],
                'defaults' => [
                    'action' => 'Home',
                ]
            ]
        ];

        foreach ($this->nonTabRecordActions as $action) {
            $config['router']['routes'][$routeName . '-' . strtolower($action)] = [
                'type'    => 'Zend\Router\Http\Segment',
                'options' => [
                    'route'    => '/:controller' . '/[:id]/' . $action,
                    'constraints' => [
                        'controller' => $controllerSpec,
                        'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ],
                    'defaults' => [
                        'action'     => $action,
                    ]
                ]
            ];
        }

    }

    public function addSearchRoute(& $config, $routeName) {
        $routeId = 'searchn-' . strtolower($routeName);
        $config['router']['routes'][$routeId] = [
            'type' => 'Zend\Router\Http\Segment',
            'options' => [
                'route' => '/:controller/' . $routeName,
                'constraints' => [
                    'controller' => 'Search([3-9][0-9]*|[1-9][0-9]+)'
                ],
                'defaults' => [
                    'action' => $routeName
                ]
            ]
        ];
    }

    public function addSearchRoutes(& $config, $routes) {
        foreach ($routes as $routeName) {
            $this->addSearchRoute($config, $routeName);
        }
    }

}
