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
namespace Bsz\Search\Params;

use Interop\Container\ContainerInterface;
use VuFind\Config\YamlReader;
use VuFind\ILS\Connection;
use VuFind\ILS\Logic\Holds;
use VuFind\ILS\Logic\TitleHolds;

/**
 * BSz Search params Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{

    /**
     * Create an object
     *
     * @param ContainerInterface $container Service manager
     * @param string $requestedName         Service being created
     * @param null|array $options           Extra options (optional)
     *
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName,
                             array $options = null
    )
    {
        // Replace trailing "Params" with "Options" to get the options service:
        $parts = explode('\\', $requestedName);
        $type = $parts[2] ?? 'Solr';
        $optionsObj = $container->get('VuFind\SearchOptionsPluginManager')->get($type);
        $configLoader = $container->get(\VuFind\Config\PluginManager::class);
        // Clone the options instance in case caller modifies it:
        $dedup = $container->get('Bsz\Config\Dedup');

        return new $requestedName(
            clone $optionsObj, $configLoader, $dedup, ...($options ?: [])
        );
    }
}
