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
namespace Bsz\Record;

use Bsz\RecordDriver\SolrGviMarc;
use VuFind\RecordDriver\AbstractBase;

/**
 * Class Router
 * @category boss
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Router extends \VuFind\Record\Router
{

    /**
     * Get routing details (route name and parameters array) to link to a record.
     *
     * @param AbstractBase|string $driver Record driver
     *                                    representing record to link to, or
     *                                    source|id pipe-delimited string
     * @param string $routeSuffix         Suffix to add
     *                                    to route name
     * @param array $extraParams          Extra parameters
     *                                    for route
     *
     * @return array
     */
    public function getRouteDetails(
        $driver,
        $routeSuffix = '',
        $extraParams = []
    ) {
        // Extract source and ID from driver or string:
        if (is_object($driver)) {
            $source = $driver->getSourceIdentifier();
            $id = $driver->getUniqueId();
        } else {
            list($source, $id) = $this->extractSourceAndId($driver);
        }

        $ill = false;
        if ($driver instanceof SolrGviMarc && !$driver->hasLocalHoldings()) {
            $ill = true;
        }

        // Build URL parameters:
        $params = $extraParams;
        $params['id'] = $id;

        // Determine route based on naming convention (default VuFind route is
        // the exception to the rule):
        $routeBase = ($source == DEFAULT_SEARCH_BACKEND)
            ? 'record' : strtolower($source . 'record');

        $routeBase = $ill ? 'ill' . $routeBase : $routeBase;

        return [
            'params' => $params, 'route' => $routeBase . $routeSuffix
        ];
    }

    /**
     * Get routing details to display a particular tab.
     *
     * @param AbstractBase|string $driver                     Record driver
     * representing record to link to, or source|id pipe-delimited string
     * @param string                                   $tab   Action to access
     * @param array                                    $query Optional query params
     *
     * @return array
     */
    public function getTabRouteDetails($driver, $tab = null, $query = [])
    {
        $route = $this->getRouteDetails(
            $driver,
            '',
            empty($tab) ? [] : ['tab' => $tab]
        );
        // Add the options and query elements only if we need a query to avoid
        // an empty element in the route definition:
        if ($query) {
            $route['options']['query'] = $query;
        }

        // If collections are active and the record route was selected, we need
        // to check if the driver is actually a collection; if so, we should switch
        // routes.
        if ($this->config->Collections->collections ?? false) {
            $routeConfig = isset($this->config->Collections->route)
                ? $this->config->Collections->route->toArray() : [];
            $collectionRoutes
                = array_merge(['record' => 'collection'], $routeConfig);
            $routeName = $route['route'];
            if ($collectionRoute = ($collectionRoutes[$routeName] ?? null)) {
                if (!is_object($driver)) {
                    // Avoid loading the driver. Set a flag so that if the link is
                    // used, record controller will check for redirection.
                    $route['options']['query']['checkRoute'] = 1;
                } elseif (true === $driver->tryMethod('isCollection')) {
                    $route['route'] = $collectionRoute;
                }
            }
        }
        return $route;
    }
}
