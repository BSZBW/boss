<?php
namespace Libadmin\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Libadmin\Institution\InstitutionLoader;

class LibrariesController extends AbstractActionController
{


    /**
     * @return ViewModel
     */
    public function indexAction()
    {
        $institutionLoader  = new InstitutionLoader();
        $viewModel          = new ViewModel();

        $viewModel->setTemplate('libraries/content');
        $viewModel->groupedInstitutions = $institutionLoader->getGroupedInstitutions();
        $requestVars = $this->getRequest()->getQuery()->toArray();
        $this->layout()->setVariable('pageClass', 'template_page');
        $viewModel->isPreview = $requestVars['preview'] == 1;

        return $viewModel;
    }

}