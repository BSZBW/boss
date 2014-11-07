<?php

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
}