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
    protected function getDateRangeSettings($savedSearch = false)
    {
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

        $tresults = $this->getResultsManager()->get('Summon');
        $tparams  = $tresults->getParams();
        $tOptions =  $tparams->getOptions();


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
}
