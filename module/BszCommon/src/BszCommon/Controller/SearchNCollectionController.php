<?php

namespace BszCommon\Controller;

class SearchNCollectionController extends \VuFind\Controller\CollectionController
{
    public function __construct(\Zend\ServiceManager\ServiceLocatorInterface $sm, \Zend\Config\Config $config, String $searchClassId)
    {
        parent::__construct($sm, $config);
        $this->searchClassId = $searchClassId;
    }

}