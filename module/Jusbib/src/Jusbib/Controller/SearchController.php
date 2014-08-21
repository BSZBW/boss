<?php
namespace Jusbib\Controller;

use Zend\Session\Container as SessionContainer;
use Zend\View\Model\ViewModel;

use Swissbib\Controller\SearchController as SwissbibSearchController;

/**
 * @package       Swissbib
 * @subpackage    Controller
 */
class SearchController extends SwissbibSearchController
{
    /**
     * Render advanced search classification trees
     *
     * @return    ViewModel
     */
    public function advancedClassificationAction()
    {
        $viewModel = parent::advancedAction();
        $viewModel->searchClassId = $this->searchClassId = 'SolrClassification'; //reset the searchClassId to its actual value

        $viewModel->setVariable('classificationTrees', $this->getServiceLocator()->get('Swissbib\Hierarchy\MultiTreeGenerator')->getTrees($viewModel->facetList));
        $viewModel->setTemplate('search/advanced');

        return $viewModel;
    }

}
