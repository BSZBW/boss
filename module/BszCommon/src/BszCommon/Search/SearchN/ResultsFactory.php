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
use BszCommon\AbstractSearchNFactory;
use Interop\Container\ContainerInterface;
use VuFind\Search\Factory\UrlQueryHelperFactory;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;

class ResultsFactory extends AbstractSearchNFactory
{

    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new Exception('Unexpected options sent to factory.');
        }

        $params = $container->get(\VuFind\Search\Params\PluginManager::class)
            ->get($requestedName);
        $searchService = $container->get(\VuFindSearch\Service::class);
        $recordLoader = $container->get(\VuFind\Record\Loader::class);
        $results = new Results(
            $params,
            $searchService,
            $recordLoader,
            $requestedName
        );
        $results->setUrlQueryHelperFactory(
            $container->get(UrlQueryHelperFactory::class)
        );
        return $results;
    }

}