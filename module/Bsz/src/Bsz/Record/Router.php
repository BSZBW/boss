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
     * @param \VuFind\RecordDriver\AbstractBase|string $driver      Record driver
     * representing record to link to, or source|id pipe-delimited string
     * @param string                                   $routeSuffix Suffix to add
     * to route name
     * @param array                                    $extraParams Extra parameters
     * for route
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

        // Build URL parameters:
        $params = $extraParams;
        $params['id'] = $id;

        // Determine route based on naming convention (default VuFind route is
        // the exception to the rule):
        $routeBase = ($source == DEFAULT_SEARCH_BACKEND)
            ? 'ill' : strtolower($source . 'lllrecord');

        return [
            'params' => $params, 'route' => $routeBase . $routeSuffix
        ];
    }
}
