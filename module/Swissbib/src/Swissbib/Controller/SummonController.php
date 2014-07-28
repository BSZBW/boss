<?php

namespace Swissbib\Controller;

use Zend\Session\Container as SessionContainer;

use VuFind\Solr\Utils as SolrUtils;
use VuFind\Controller\SummonController as VuFindSummonController;

class SummonController extends VuFindSummonController
{

    /**
     * Get date range settings for summon
     * Field is named PublicationDate instead publishDate
     *
     * @param    Boolean        $savedSearch
     * @return    Array
     */
    protected function getDateRangeSettings($savedSearch = false, $config = 'facets',
      $filter = array()
    ) {
        // Default to blank strings:
        $from = $to = '';

        // Check to see if there is an existing range in the search object:
        if ($savedSearch) {
            $filters = $savedSearch->getParams()->getFilters();
            if (isset($filters['PublicationDate'])) {
                foreach ($filters['PublicationDate'] as $current) {
                    if ($range = SolrUtils::parseRange($current)) {
                        $from = $range['from'] == '*' ? '' : $range['from'];
                        $to = $range['to'] == '*' ? '' : $range['to'];
                        $savedSearch->getParams()
                            ->removeFilter('PublicationDate:' . $current);
                        break;
                    }
                }
            }
        }

        // Send back the settings:
        return array($from, $to);
    }



    /**
     * Return a Search Results object containing advanced facet information.  This
     * data may come from the cache.
     *
     * @return \VuFind\Search\Summon\Results
     */
    protected function getAdvancedFacets()
    {
        // Check if we have facet results cached, and build them if we don't.
        $cache = $this->getServiceLocator()->get('VuFind\CacheManager')
                ->getCache('object');

        if (!($results = $cache->getItem('summonSearchAdvancedFacets'))) {
            $results = $this->getResultsManager()->get('Summon');
            $params  = $results->getParams();
            $params->addFacet('Language,or,1,20');
            $params->addFacet('ContentType,or,1,20', 'Format');

            // We only care about facet lists, so don't get any results:
            $params->setLimit(0);

            // force processing for cache
            $results->getResults();

            $cache->setItem('summonSearchAdvancedFacets', $results);
        }

        // Restore the real service locator to the object (it was lost during
        // serialization):
        $results->restoreServiceLocator($this->getServiceLocator());

        return $results;
    }



    /**
     * Get results manager
     * If target is extended, get a customized manager
     * @todo  Same method as in Swissbib/Controller/SearchController. Extract!
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



    /**
     * @return void|\VuFind\Search\Summon\Results
     */
    protected function getHomePageFacets()
    {
        return $this->getFacetResults('initHomePageFacets', 'summonSearchHomeFacets');
    }



    /**
     * Return a Search Results object containing requested facet information.  This
     * data may come from the cache.
     *
     * @param string $initMethod Name of params method to use to request facets
     * @param string $cacheName  Cache key for facet data
     *
     * @return \VuFind\Search\Solr\Results
     */
    protected function getFacetResults($initMethod, $cacheName)
    {
        // Check if we have facet results cached, and build them if we don't.
        $cache = $this->getServiceLocator()->get('VuFind\CacheManager')
            ->getCache('object');
        if (!($results = $cache->getItem($cacheName))) {
            // Use advanced facet settings to get summary facets on the front page;
            // we may want to make this more flexible later.  Also keep in mind that
            // the template is currently looking for certain hard-coded fields; this
            // should also be made smarter.
            $results = $this->getResultsManager()->get('Summon');
            $params = $results->getParams();
            $params->$initMethod();

            // We only care about facet lists, so don't get any results (this helps
            // prevent problems with serialized File_MARC objects in the cache):
            $params->setLimit(0);

            $results->getResults();                     // force processing for cache

            $cache->setItem($cacheName, $results);
        }

        // Restore the real service locator to the object (it was lost during
        // serialization):
        $results->restoreServiceLocator($this->getServiceLocator());
        return $results;
    }
}
