<?php
namespace Swissbib\VuFind\Search\Solr;

use VuFind\Search\Solr\Results as VuFindSolrResults;
use VuFindSearch\Query\AbstractQuery;
use VuFindSearch\Query\QueryGroup;
use VuFindSearch\ParamBag;

use Swissbib\Favorites\Manager;

/**
 * Class to extend the core VF2 SOLR functionality related to Solr Results
 */
class Results extends VuFindSolrResults
{

    /**
     * @var String
     */
    protected $target = 'swissbib';


    /**
     * Create backend parameters
     * Add facet queries for user institutions
     *
     * @param    AbstractQuery    $query
     * @param    Params            $params
     * @return    ParamBag
     */
    protected function createBackendParameters(AbstractQuery $query, Params $params)
    {


        //obsolete function
        //$backendParams = parent::createBackendParameters($query, $params);

        //with SOLR 4.3 AND is no longer the default parameter
        //$backendParams->add("q.op", "AND");

        //$backendParams = $this->addUserInstitutions($backendParams);

        //return $backendParams;
    }



    protected function createSpellcheckBackendParameters(AbstractQuery $query, Params $params)
    {
        $backendParams = parent::createBackendParameters($query, $params);

        //with SOLR 4.3 AND is no longer the default parameter
        $backendParams->add("q.op", "AND");

        $backendParams->add("spellcheck", "true");
        $spelling = $query->getAllTerms();
        if ($spelling) {
            $backendParams->set('spellcheck.q', $spelling);
            $this->spellingQuery = $spelling;
        }



        //$backendParams = $this->addUserInstitutions($backendParams);

        return $backendParams;
    }




    /**
     * Add user institutions as facet queries to backend params
     *
     * @param    ParamBag    $backendParams
     * @return    ParamBag
     */
    //todo: this function was moved to the params type - could be deleted?
    //at the moment no time for testing - to be done later (GH)
    protected function addUserInstitutions(ParamBag $backendParams)
    {
        /** @var Manager $favoritesManger */
        $favoritesManger        = $this->getServiceLocator()->get('Swissbib\FavoriteInstitutions\Manager');
        /** @var String[] $favoriteInstitutions */
        $favoriteInstitutions    = $favoritesManger->getUserInstitutions();

        if (sizeof($favoriteInstitutions > 0)) {
                //facet parameter has to be true in case it's false
            $backendParams->set("facet", "true");

            foreach ($favoriteInstitutions as $institutionCode) {
                //$backendParams->add("facet.query", "institution:" . $institutionCode);
                $backendParams->add("bq", "institution:" . $institutionCode . "^5000");
            }
        }

        return $backendParams;
    }



    /**
     * Get facet queries from result
     * Data is extracted
     * Format: {field, value, count, name}
     *
     * @param    Boolean        $onlyNonZero
     * @return    Array[]
     */
    protected function getResultQueryFacets($onlyNonZero = false)
    {
        /** @var \ArrayObject $queryFacets */
        $queryFacets = $this->responseFacets->getQueryFacets();
        $facets        = array();

        foreach ($queryFacets as $facetName => $queryCount) {
            list($fieldName,$filterValue) = explode(':', $facetName, 2);

            if (!$onlyNonZero || $queryCount > 0) {
                $facets[] = array(
                    'field'    => $fieldName,
                    'value'    => $filterValue,
                    'count'    => $queryCount,
                    'name'    => $facetName
                );
            }
        }

        return $facets;
    }



    /**
     * Get special facets
     * - User favorite institutions
     *
     * @return    Array[]
     */
    public function getSpecialFacets()
    {
        $queryFacets    = $this->getResultQueryFacets(true);
        $facetListItems    = array();

        foreach ($queryFacets as $queryFacet) {
            if ($queryFacet['field'] === 'institution') {
                $sortKey    = sprintf('%09d', $queryFacet['count']) . '_' . $queryFacet['value']; // Sortable but unique key

                $facetListItems[$sortKey] = array(
                    'value'            => $queryFacet['value'],
                    'displayText'    => $queryFacet['value'],
                    'count'            => $queryFacet['count'],
                    'isApplied'        => $this->getParams()->hasFilter($queryFacet['name'])
                );
            }
        }

        if (empty($facetListItems)) {
            return array();
        }

            // Sort by count (which is the key)
        krsort($facetListItems);
        $facetListItems = array_values($facetListItems);

        return array(
            'institution' => array(
                'label'    => 'mylibraries',
                'field'    => 'institution',
                'list'    => $facetListItems
            )
        );
    }



    /**
     * Get facet list
     * Add institution query facets on top of the list
     *
     * @param    Array|Null        $filter
     * @return    Array[]
     */
    public function getFacetList($filter = null)
    {


        /* start of VF2 implementation - has to be re-changed once multi domain translations even for factes are implemented*/

        // Make sure we have processed the search before proceeding:
        if (null === $this->responseFacets) {
            $this->performAndProcessSearch();
        }

        // If there is no filter, we'll use all facets as the filter:
        if (is_null($filter)) {
            $filter = $this->getParams()->getFacetConfig();
        }

        // Start building the facet list:
        $list = array();

        // Loop through every field returned by the result set
        $fieldFacets = $this->responseFacets->getFieldFacets();
        foreach (array_keys($filter) as $field) {
            $data = isset($fieldFacets[$field]) ? $fieldFacets[$field] : array();
            // Skip empty arrays:
            if (count($data) < 1) {
                continue;
            }
            // Initialize the settings for the current field
            $list[$field] = array();
            // Add the on-screen label
            $list[$field]['label'] = $filter[$field];
            // Build our array of values for this field
            $list[$field]['list']  = array();
            // Should we translate values for the current facet?
            //$translate
            //    = in_array($field, $this->getOptions()->getTranslatedFacets());


            $refValuesToTranslate =&  $this->getOptions()->getTranslatedFacets();
            $fieldToTranslateInArray =  array_filter($refValuesToTranslate,function ($passedValue) use ($field){
                return $passedValue === $field || count(preg_grep ( "/" .$field . ":" . "/", array ($passedValue))) > 0;
            }) ;

            $translate = count($fieldToTranslateInArray) > 0;
            $fieldToEvaluate = $translate ? current($fieldToTranslateInArray) : null;

            // Loop through values:
            foreach ($data as $value => $count) {
                // Initialize the array of data about the current facet:
                $currentSettings = array();
                $currentSettings['value'] = $value;
                $currentSettings['displayText']
                    = $translate ? strstr($fieldToEvaluate,':') === FALSE ? $this->translate($value) :
                                        $this->translate(array($value , substr($fieldToEvaluate,strpos( $fieldToEvaluate,':') + 1 )))  : $value;

                //$currentSettings['displayText']
                //    = $translate ?  $this->translate($value) : $value;


                $currentSettings['count'] = $count;
                $currentSettings['operator']
                    = $this->getParams()->getFacetOperator($field);
                $currentSettings['isApplied']
                    = $this->getParams()->hasFilter("$field:".$value)
                    || $this->getParams()->hasFilter("~$field:".$value);

                // Store the collected values:
                $list[$field]['list'][] = $currentSettings;
            }
        }
        return $list;



        /* end of VF2 implementation */

        /* I guess we need this for QueryFacets -> to be done

        $facetList     = parent::getFacetList($filter);
        $facetList     = $this->getFacetList($filter);
        $specialFacets = array();

        if( in_array('mylibraries', $filter)) {
          $specialFacets = $this->getSpecialFacets();
        }

            // Prepend special facets
        $facetList = $specialFacets + $facetList;

        return $facetList;
        */

    }






    /**
     * @return String $target
     */
    public function getTarget()
    {
        return $this->target;
    }


    protected function performSearch()
    {




        $query  = $this->getParams()->getQuery();
        $limit  = $this->getParams()->getLimit();
        $offset = $this->getStartRecord() - 1;
        $params = $this->getParams()->getBackendParameters();
        $searchService = $this->getSearchService();

        try {
            $collection = $searchService
                ->search($this->backendId, $query, $offset, $limit, $params);
        } catch (\VuFindSearch\Backend\Exception\BackendException $e) {
            // If the query caused a parser error, see if we can clean it up:
            if ($e->hasTag('VuFind\Search\ParserError')
                && $newQuery = $this->fixBadQuery($query)
            ) {
                // We need to get a fresh set of $params, since the previous one was
                // manipulated by the previous search() call.
                $params = $this->getParams()->getBackendParameters();
                $collection = $searchService
                    ->search($this->backendId, $newQuery, $offset, $limit, $params);
            } else {
                throw $e;
            }
        }


        //code aus letztem VuFind Core
        $this->responseFacets = $collection->getFacets();
        $this->resultTotal = $collection->getTotal();

        // Process spelling suggestions
        //$spellcheck = $collection->getSpellcheck();
        //$this->spellingQuery = $spellcheck->getQuery();
        //$this->suggestions = $this->getSpellingProcessor()
        //    ->getSuggestions($spellcheck, $this->getParams()->getQuery());

        // Construct record drivers for all the items in the response:
        //$this->results = $collection->getRecords();


        if ($this->resultTotal == 0) {

            //we use spellchecking only in case of 0 hits

            //$params = $this->createSpellcheckBackendParameters($query, $this->getParams());
            //$collectionSpell = $this->getSearchService()
            //    ->search($this->backendId, $query, $offset, $limit, $params);

            // Process spelling suggestions
            //$spellcheck = $collectionSpell->getSpellcheck();
            //$this->processSpelling($spellcheck);

            $params = $this->getParams()->getSpellcheckBackendParameters();
            try {
                $collectionSpelling = $searchService
                    ->search($this->backendId, $query, $offset, $limit, $params);
            } catch (\VuFindSearch\Backend\Exception\BackendException $e) {
                // If the query caused a parser error, see if we can clean it up:


                //we don't throw spelling exceptions but we should
                throw $e;

            }


            // Process spelling suggestions
            $spellcheck = $collectionSpelling->getSpellcheck();
            $this->spellingQuery = $spellcheck->getQuery();
            $this->suggestions = $this->getSpellingProcessor()
                ->getSuggestions($spellcheck, $this->getParams()->getQuery());



        }


        // Construct record drivers for all the items in the response:
        $this->results = $collection->getRecords();






    }



}
