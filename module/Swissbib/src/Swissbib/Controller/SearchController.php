<?php
namespace Swissbib\Controller;

use Zend\Config\Config;
use Zend\Http\PhpEnvironment\Response;
use Zend\Session\Container as SessionContainer;
use Zend\View\Model\ViewModel;

use VuFind\Controller\SearchController as VuFindSearchController;
use VuFind\Search\Results\PluginManager as VuFindSearchResultsPluginManager;

use Swissbib\VuFind\Search\Results\PluginManager as SwissbibSearchResultsPluginManager;

/**
 * @package       Swissbib
 * @subpackage    Controller
 */
class SearchController extends VuFindSearchController
{

    /**
     * @var    String[]   search targets extended by swissbib
     */
    protected $extendedTargets;



    /**
     * Get model for general results view (all tabs, content of active tab only)
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function resultsAction()
    {
        $resultsFacetConfig = $this->getFacetConfig();
        //do not remember FRBR searches because we ant to jump back to the original search
        $type = $this->params()->fromQuery('type');

        if (!empty($type) && $type == "FRBR") {
            $this->rememberSearch = false;
        }

        $resultViewModel = parent::resultsAction();

        if ($resultViewModel instanceof Response) {
            return $resultViewModel;
        }

        $this->layout()->setVariable('resultViewParams', $resultViewModel->getVariable('params'));
        $resultViewModel->setVariable('facetsConfig', $resultsFacetConfig);

        return $resultViewModel;
    }



    /**
     * Render advanced search
     *
     * @return    ViewModel
     */
    public function advancedAction()
    {
        $allTabsConfig          = $this->getThemeTabsConfig();
        $activeTabKey           = $this->getActiveTab();
        $activeTabConfig        = $allTabsConfig[$activeTabKey];
        $this->searchClassId    = $activeTabConfig['searchClassId'];
        $viewModel              = parent::advancedAction();
        $viewModel->options     = $this->getServiceLocator()->get('Swissbib\SearchOptionsPluginManager')->get($this->searchClassId);
        $results                = $this->getResultsManager()->get($this->searchClassId);

        $viewModel->setVariable('allTabsConfig', $allTabsConfig);
        $viewModel->setVariable('activeTabKey', $activeTabKey);
        $viewModel->setVariable('params', $results->getParams());

        $mainConfig = $this->getServiceLocator()->get('Vufind\Config')->get('config');
        $viewModel->adv_search_activeTabId = $mainConfig->Site->adv_search_activeTabId;
        $viewModel->adv_search_useTabs     = $mainConfig->Site->adv_search_useTabs;
        $isCatTreeElementConfigured = $mainConfig->Site->displayCatTreeElement;
        $isCatTreeElementConfigured = !empty($isCatTreeElementConfigured) && ($isCatTreeElementConfigured == "true" || $isCatTreeElementConfigured == "1") ? "1" : 0;

        if ($isCatTreeElementConfigured) {
            $treeGenerator                   = $this->serviceLocator->get('Swissbib\Hierarchy\SimpleTreeGenerator');
            $viewModel->classificationTree   = $treeGenerator->getTree($viewModel->facetList['navDrsys_Gen']['list'], 'navDrsys_Gen');
        }

        return $viewModel;
    }



    /**
     * Get facet config
     *
     * @return    Config
     */
    protected function getFacetConfig()
    {
        return $this->getServiceLocator()->get('VuFind\Config')->get('facets')->get('Results_Settings');
    }



    /**
     * Get results manager
     * If target is extended, get a customized manager
     *
     * @return    VuFindSearchResultsPluginManager|SwissbibSearchResultsPluginManager
     */
    protected function getResultsManager()
    {
        if (!isset($this->extendedTargets)) {
            $mainConfig = $this->getServiceLocator()->get('Vufind\Config')->get('config');
            $extendedTargetsSearchClassList = $mainConfig->SwissbibSearchExtensions->extendedTargets;

            $this->extendedTargets = array_map('trim', explode(',', $extendedTargetsSearchClassList));
        }

        if (in_array($this->searchClassId, $this->extendedTargets)) {
            return $this->getServiceLocator()->get('Swissbib\SearchResultsPluginManager');
        }

        return parent::getResultsManager();
    }
}
