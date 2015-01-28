<?php

/**
 * Multiple Backend Driver : Swissbib extensions
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland, 2014
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind
 * @package  ILSdrivers
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @author   Oliver Schihin <oliver.schihin@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_an_ils_driver Wiki
 * @link     http://www.swissbib.org Project Wiki
 */

namespace Swissbib\VuFind\ILS\Driver;
use VuFind\ILS\Driver\MultiBackend as VFMultiBackend,
    VuFind\Exception\ILS as ILSException,
    Zend\ServiceManager\ServiceLocatorAwareInterface,
    Zend\ServiceManager\ServiceLocatorInterface,
    Zend\Log\LoggerInterface;

class MultiBackend extends VFMultiBackend {

    public function getBookings($id) {
        $source = $this->getSource($id);
        $driver = $this->getDriver($source);
        if ($driver) {
            return $driver->getBookings($this->getLocalId($id));
        }
        return array();
    }

    public function getPhotoCopies($id) {
        $source = $this->getSource($id);
        $driver = $this->getDriver($source);
        if ($driver) {
            return $driver->getPhotoCopies($this->getLocalId($id));
        }
        return array();
    }

    public function getAllowedActionsForItem($patronId, $id, $group, $bib) {
        $source = $this->getSource($patronId);
        $driver = $this->getDriver($source);
        if ($driver) {
            return $driver->getAllowedActionsForItem($this->getLocalId($patronId), $id, $group, $bib);
        }
        return array();
    }

    public function getRequiredDate($patron, $holdInfo=null) {
        $id = $patron['id'];
        $source = $this->getSource($id);
        $driver = $this->getDriver($source);
        if ($driver) {
            return $driver->getRequiredDate($patron, $holdInfo);
        }
        return array();
    }

    public function getPickUpLocations($patron = false, $holdDetails = null)
    {
        $source = $this->getSource($patron['cat_username'], 'login');
        $driver = $this->getDriver($source);
        if ($driver) {
            if ($holdDetails) {
                $locations = $driver->getPickUpLocations(
                    $this->stripIdPrefixes($patron, $source),
                    $this->stripIdPrefixes($holdDetails, $source)
                );
                return $this->addIdPrefixes($locations, $source);
            }
            throw new ILSException('No suitable backend driver found');
        }
    }

    public function placeHold($holdDetails)
    {
        $source = $this->getSource($holdDetails['patron']['cat_username'], 'login');
        $driver = $this->getDriver($source);
        if ($driver) {
            $holdDetails = $this->stripIdPrefixes($holdDetails, $source);
            return $driver->placeHold($holdDetails);
        }
        throw new ILSException('No suitable backend driver found');
    }

    /**
     * The following functions are implementations of a "Basel Bern" functionality, display of journal volumes to order
     */

    public function getHoldingHoldingItems(
        $resourceId,
        $institutionCode = '',
        $offset = 0,
        $year = 0,
        $volume = 0,
        $numItems = 10,
        array $extraRestParams = array()
    )
    {
        $source = $this->getSource($resourceId);
        $driver = $this->getDriver($source);
        if ($driver) {
            return $driver->getHoldingHoldingItems(
                $resourceId,
                $institutionCode,
                $offset = 0,
                $year = 0,
                $volume = 0,
                $numItems = 10,
                $extraRestParams = array()
                );
        }
    }

    public function getHoldingItemCount($resourceId, $institutionCode = '') {
        $source = $this->getSource($resourceId);
        $driver = $this->getDriver($source);
        if ($driver) {
            return $driver->getHoldingItemCount(
                $resourceId,
                $institutionCode
                );
        }
        throw new ILSException('No suitable backend driver found');
    }

    public function getResourceFilters($resourceId) {
        $source = $this->getSource($resourceId);
        $driver = $this->getDriver($source);
        if ($driver) {
            return $driver->getResourceFilters($resourceId);
        }
    }

    /**
     * Extract source from the given ID
     *
     * @param string $id        The id to be split
     * @param string $delimiter The delimiter to be used from $this->delimiters
     *
     * @return string  Source
     *
     * Circumvent the private declaration in parent class
     */

    public function getSource($id, $delimiter = '') {
        return parent::getSource($id, $delimiter = '');
    }

    /**
     * Get configuration for the ILS driver.  We will load an .ini file named
     * after the driver class and number if it exists;
     * otherwise we will return an empty array.
     *
     * @param string $source The source id to use for determining the
     * configuration file
     *
     * @return array   The configuration of the driver
     *
     * Circumvent the private declaration in parent class
     */

    public function getDriverConfig($source) {
        return parent::getDriverConfig($source);
    }

}