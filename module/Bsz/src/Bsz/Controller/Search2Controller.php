<?php

namespace Bsz\Controller;

use VuFind\XSLT\Import\VuFind;

class Search2Controller extends \VuFind\Controller\Search2Controller
{
    use IsilTrait;

    public function resultsAction()
    {
        $dedup = $this->serviceLocator->get('Bsz\Config\Dedup');
        $isils = $this->params()->fromQuery('isil');
        if ($isils) {
            return $this->processIsil();
        }
        $view = Parent::resultsAction();
        $view->dedup = $dedup->isActive();
        return $view;
    }
}