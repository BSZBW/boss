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

namespace BszCommon\Controller;

use Bsz\Exception;

class AbstractBaseFactory implements
    \Zend\ServiceManager\Factory\AbstractFactoryInterface
{
    private $regex = '/(Search(?:[3-9][0-9]*|[1-9][0-9]+))([a-zA-Z]+|)/';

    public function canCreate(
        \Interop\Container\ContainerInterface $container,
        $requestedName
    ) {
        return preg_match($this->regex, $requestedName);
    }

    public function __invoke(
        \Interop\Container\ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new Exception('Unexpected options sent to factory.');
        }

        $matches = [];
        if (!preg_match($this->regex, $requestedName, $matches)) {
            throw new Exception('Unexpected name sent to factory');
        }


        $searchClassId = $matches[1];
        if (strtolower($matches[2]) === 'collection') {
            $config = $container->get(\VuFind\Config\PluginManager::class)
                ->get('config');
            return new SearchNCollectionController(
                $container,
                $config,
                $searchClassId
            );
        }
        $baseName = ($matches[2] === 'collectionrecord') ? 'record'
            : $matches[2];
        $className = __NAMESPACE__ . '\\' . 'SearchN' . ucfirst($baseName)
            . 'Controller';
        return new $className($container, $searchClassId);
    }
}
