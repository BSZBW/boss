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
namespace Bsz\Controller;

use Exception;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Factory for all controllers which needs params
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new Exception('Unexpected options sent to factory.');
        }
        return new $requestedName($container);
    }

    /**
     * Construct the RecordController.
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return RecordController
     */
    public static function getRecordController(ContainerInterface $container)
    {
        return new RecordController(
            $container,
            $container->get('VuFind\Config')->get('config')
        );
    }
    /**
     * Construct the ILLRecordController.
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return ILLRecordController
     */
    public static function getILLRecordController(ContainerInterface $container)
    {
        return new ILLRecordController(
            $container,
            $container->get('VuFind\Config')->get('config')
        );
    }
}
