<?php

namespace BszCommon\Controller;

use BszCommon\Controller\Plugin\UrlDecorator;

class SearchNController extends \VuFind\Controller\AbstractSolrSearch
{
    public function __construct(\Zend\ServiceManager\ServiceLocatorInterface $sm, String $searchClassId)
    {
        $this->searchClassId = $searchClassId;
        parent::__construct($sm);
    }

    protected function resultScrollerActive(): bool
    {
        $config = $this->serviceLocator->get(\VuFind\Config::class)
            ->get($this->searchClassId);
        return isset($config->Record->next_prev_navigation)
            && $config->Record->next_prev_navigation;
    }

    public function url(): \Zend\Mvc\Controller\Plugin\Url
    {
        return new UrlDecorator(parent::url());
    }

}
