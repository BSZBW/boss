<?php
namespace Bsz\Controller;

/**
 * Add flash messages to search Controller
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SearchController extends \VuFind\Controller\SearchController
{
    use IsilTrait;

    /**
     * Home action
     *
     * @return mixed
     */
    public function homeAction()
    {
        $view = parent::homeAction();
        $msg = getenv('MAINTENANCE_MODE');
        if ($msg != '') {
            $this->flashMessenger()->addWarningMessage($msg);
        }
        $request = $this->getRequest();
        $referer = $request->getHeader('referer');

        if (is_object($referer) &&
            (strpos($referer->getUri(), '.boss') !== false
                || strpos($referer->getUri(), 'localhost') !== false)
        ) {
            $view->referer = $referer;
        }
        return $view;
    }

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

    /**
     * Taken from AbstractSolrSearch class
     * Process the facets to be used as limits on the Advanced Search screen.
     *
     * @param array  $facetList          The advanced facet values
     * @param object $searchObject       Saved search object (false if none)
     * @param array  $hierarchicalFacets Hierarchical facet list (if any)
     *
     * @return array               Sorted facets, with selected values flagged.
     */
    protected function processAdvancedFacets(
        $facetList,
        $searchObject = false,
        $hierarchicalFacets = []
    ) {
        // Process the facets
        $facetHelper = null;
        if (!empty($hierarchicalFacets)) {
            $facetHelper = $this->serviceLocator
                ->get(\VuFind\Search\Solr\HierarchicalFacetHelper::class);
        }
        foreach ($facetList as $facet => &$list) {
            // Hierarchical facets: format display texts and sort facets
            // to a flat array according to the hierarchy
            if (in_array($facet, $hierarchicalFacets)) {
                $tmpList = $list['list'];
                $facetHelper->sortFacetList($tmpList, true);
                $tmpList = $facetHelper->buildFacetArray(
                    $facet,
                    $tmpList
                );
                $list['list'] = $facetHelper->flattenFacetHierarchy($tmpList);
            }

            foreach ($list['list'] as $key => $value) {
                // Build the filter string for the URL:
                $fullFilter = ($value['operator'] == 'OR' ? '~' : '')
                    . $facet . ':"' . $value['value'] . '"';

                // If we haven't already found a selected facet and the current
                // facet has been applied to the search, we should store it as
                // the selected facet for the current control.
                if ($searchObject
                    && $searchObject->getParams()->hasFilter($fullFilter)
                ) {
                    $list['list'][$key]['selected'] = true;
                    // Remove the filter from the search object -- we don't want
                    // it to show up in the "applied filters" sidebar since it
                    // will already be accounted for by being selected in the
                    // filter select list!
                    $searchObject->getParams()->removeFilter($fullFilter);
                }
            }
        }
        return $this->filterFacetSet($facetList);
    }
    /**
     * Filter out any unwanted facets
     * @param array $facetSet
     */
    public function filterFacetSet($facetSet)
    {
        $this->filterFacets = $this->serviceLocator->get('VuFind\Config')->get('facets')->get('Filter_Facets');
        if (isset($this->filterFacets)) {
            foreach ($this->filterFacets as $facet => $filter) {
                if (isset($facetSet[$facet])) {

                    foreach ($facetSet[$facet]['list'] as $key => $originalFacet) {
                        if (!$this->checkFilter($filter, $originalFacet['value'])) {
                            //unset facet values we do not want
                            unset($facetSet[$facet]['list'][$key]);
                        }
                    }
                    if (count($facetSet[$facet]['list']) < 1) {
                        // No Facets remained - remove the facet
                        unset($facetSet[$facet]);
                    } else {
                        //Re-number the array
                        $facetSet[$facet]['list'] = array_values($facetSet[$facet]['list']);
                    }
                }
            }
        }
        return $facetSet;
    }

    private function checkFilter($filter, $value)
    {
        $allowed = explode(',', $filter);
        foreach ($allowed as $a) {
            $a = '/^'.$a.'/i';
            if (preg_match($a, $value)) {
                return true;
            }
        }
        return false;
    }
}
