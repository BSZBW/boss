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

    public function getHoldingInfoForItem() {
        return null;
    }
}