<?php

namespace BszCommon\Controller;

use Zend\ServiceManager\ServiceLocatorInterface;

class SearchNRecordController extends \VuFind\Controller\AbstractRecord
{

    public function __construct(ServiceLocatorInterface $sm, string $searchClassId)
    {
        $this->searchClassId = $searchClassId;
        $this->fallbackDefaultTab = 'Description';
        parent::__construct($sm);
    }

    protected function resultScrollerActive()
    {
        $config = $this->serviceLocator->get(\VuFind\Config::class)
            ->get($this->searchClassId);
        return isset($config->Record->next_prev_navigation)
            && $config->Record->next_prev_navigation;
    }

}