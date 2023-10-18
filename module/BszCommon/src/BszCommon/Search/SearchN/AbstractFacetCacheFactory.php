<?php

/*
 * Copyright (C) 2023 Bibliotheks-Service Zentrum, Konstanz, Germany
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

namespace BszCommon\Search\SearchN;

use Bsz\Exception;
use Interop\Container\ContainerInterface;

class AbstractFacetCacheFactory extends \BszCommon\AbstractSearchNFactory
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
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
        $language = $container->get(\Zend\Mvc\I18n\Translator::class)
            ->getLocale();
        return new FacetCache(
            $results,
            $cacheManager,
            $requestedName,
            $language
        );
    }
}
