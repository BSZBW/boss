<?php
namespace Swissbib\VuFind\Search\Solr;

use VuFind\Search\Solr\Results as VuFindSolrResults;
use VuFindSearch\Query\AbstractQuery;
use VuFindSearch\Query\QueryGroup;
use VuFindSearch\ParamBag;

use Swissbib\Favorites\Manager;
use VuFind\Search\Solr\SpellingProcessor;

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
     * @var SpellingResults
     */
    protected $sbSuggestions;




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

        //GH 19.12.2014
        //this might need a redesign. It's the old implementation for swissbib classic
        //where a dedicated template for favorites was used and the favorite facets (QueryFacets) were totally separated from the Term facets
        //aim: Integration of QueryFacets as part of the Facet (recommendation) component of VF2 (Top, side etc)
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
    public function getMyLibrariesFacets()
    {
        $queryFacets    = $this->getResultQueryFacets(true);
        $list = array();

        $configQuerySettings = $this->getServiceLocator()->get('VuFind\Config')
            ->get($this->getOptions()->getFacetsIni())->QueryFacets;
        if (count($queryFacets) > 0 && isset($configQuerySettings))
        {
            $configResultSettings = $this->getServiceLocator()->get('VuFind\Config')
                ->get($this->getOptions()->getFacetsIni())->Results_Settings;


            foreach ($queryFacets as $queryFacet) {

                if (isset($configQuerySettings[$queryFacet['field']]))
                {
                    $facetGroupName = $queryFacet['field'];


                    if (!isset($list[$facetGroupName])) {
                        $list[$facetGroupName] = array();
                    }
                    if (!isset($list[$facetGroupName]['label'])) {
                        $list[$facetGroupName]['label'] = $configQuerySettings[$queryFacet['field']];
                    }



                    $translateInfo = $this->isFieldToTranslate($queryFacet['field']);

                    if (!isset($list[$facetGroupName]['displayLimit'])) {
                        $list[$facetGroupName]['displayLimit'] = isset($configResultSettings->{'facet_limit_' . $translateInfo['normalizedFieldName']}) ?
                            $configResultSettings->{'facet_limit_' . $translateInfo['normalizedFieldName']} :  $configResultSettings->facet_limit_default;

                        //$list[$facetGroupName]['displayLimit'] = 1;
                    }

                    if (!isset($list[$facetGroupName]['field'])) {
                        $list[$facetGroupName]['field'] = $facetGroupName;
                    }


                    if (!isset($list[$facetGroupName]['list']))
                    {
                        $list[$facetGroupName]['list'] = array();
                    }


                    $currentSettings = array();

                    $currentSettings['displayText']
                        = $translateInfo['translate'] ? count($translateInfo['field_domain']) == 1 ? $this->translate($queryFacet['value']) :
                        $this->translate(array($queryFacet['value'] , $translateInfo['field_domain'][1]))  : $queryFacet['value'];
                    //$currentSettings['isApplied'] = $this->getParams()->hasFilter($queryFacet['name']);

                    $currentSettings['isApplied'] = $this->getParams()->hasFilter($facetGroupName .":".$queryFacet['value'])
                                    || $this->getParams()->hasFilter("~" . $facetGroupName . ":".$queryFacet['value']);

                    $currentSettings['count'] = $queryFacet['count'];
                    $currentSettings['value'] = $queryFacet['value'];


                    $list[$facetGroupName]['list'][] = $currentSettings;

                }

            }

        }

        return $list;

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

        //how Facet-Configuration is used seems to be weired for me
        $configResultSettings = $this->getServiceLocator()->get('VuFind\Config')
            ->get($this->getOptions()->getFacetsIni())->Results_Settings;

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


            $translateInfo = $this->isFieldToTranslate($field);


            $list[$field]['displayLimit'] = isset($configResultSettings->{'facet_limit_' . $translateInfo['normalizedFieldName']}) ?
                $configResultSettings->{'facet_limit_' . $translateInfo['normalizedFieldName']} :  $configResultSettings->facet_limit_default;
            // Loop through values:
            foreach ($data as $value => $count) {
                // Initialize the array of data about the current facet:
                $currentSettings = array();
                $currentSettings['value'] = $value;

                //if translation should be done (flag -translate) we have to distinguis between
                //a) multi domain (field contains a colon). Then the signature of the translation method differs (domain has to be indicated)
                //b) or simple translation

                $currentSettings['displayText']
                    = $translateInfo['translate'] ? count($translateInfo['field_domain']) == 1 ? $this->translate($value) :
                                        $this->translate(array($value , $translateInfo['field_domain'][1]))  : $value;

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


        if ($this->resultTotal == 0) {

            //we use spellchecking only in case of 0 hits

            $params = $this->getParams()->getSpellcheckBackendParameters();
            try {
                $recordCollectionSpellingQuery = $searchService
                    ->search($this->backendId, $query, $offset, $limit, $params);
            } catch (\VuFindSearch\Backend\Exception\BackendException $e) {
                //todo: some kind of logging?
                throw $e;

            }


            // Processing of spelling suggestions
            $spellcheck = $recordCollectionSpellingQuery->getSpellcheck();
            $this->spellingQuery = $spellcheck->getQuery();

            //GH: I introduced a special type for suggestions provided by the SOLR index
            //in opposition to the VF2 core implementation where a simple array structure is used
            //a specialized type makes it much easier to use the suggestions in the view script
            //the object variable suggestions is already used by VF2 core
            $this->sbSuggestions = $this->getSpellingProcessor()
                ->getSuggestions($spellcheck, $this->getParams()->getQuery());

            //$this->suggestions = $this->getSpellingProcessor()
            //    ->getSuggestions($spellcheck, $this->getParams()->getQuery());

        }

        // Construct record drivers for all the items in the response:
        $this->results = $collection->getRecords();


    }


    public function getSpellingProcessor()
    {
        if (null === $this->spellingProcessor) {
            $this->spellingProcessor = $this->getServiceLocator()->get("sbSpellingProcessor");
        }
        return $this->spellingProcessor;
    }


    /*
     * Utility method to inspect multi domain translation for facets
     * @return array()
     */
    protected function isFieldToTranslate($field)
    {
        $translateInfo = array();

        //getTranslatedFacets returns the entries in Advanced_Settings -> translated_facets
        $refValuesToTranslate = $this->getOptions()->getTranslatedFacets();
        //is the current field a facet which should be translated?
        //we have to use this customized filter mechanism because facets going to be translated are indicated in conjunction with their domain facetName:domainName
        $fieldToTranslateInArray =  array_filter($refValuesToTranslate,function ($passedValue) use ($field){
            //return true, if the field shoul be translated
            //either $field==value in arra with facets to be translated (simple translation)
            //or multi domain translation where the domain is part of the configuration fieldname:domainName
            return $passedValue === $field || count(preg_grep ( "/" .$field . ":" . "/", array ($passedValue))) > 0;
        }) ;

        //Did we detect the field should be translated (field is part of the filtered array)
        $translateInfo['translate'] = count($fieldToTranslateInArray) > 0;
        //this name is always without any colons and could be used in further processing
        $translateInfo['normalizedFieldName'] = $field;
        $translateInfo['field_domain'] = array();

        $fieldToTranslate = $translateInfo['translate'] ? current($fieldToTranslateInArray) : null;
        if ($translateInfo['translate']) {
            $translateInfo['field_domain'] =  strstr($fieldToTranslate,':') === FALSE ? array($field) : array($field,substr($fieldToTranslate,strpos( $fieldToTranslate,':') + 1 ));
            //normalizedFieldName contains only the fieldname without any colons as seperator for the domain name (it's handy)
            $translateInfo['normalizedFieldName'] = $translateInfo['field_domain'][0];
        }

        return $translateInfo;
    }

    /**
     * Turn the list of spelling suggestions into an array of urls
     *   for on-screen use to implement the suggestions.
     *
     * @return array Spelling suggestion data arrays
     */
    public function getSpellingSuggestions()
    {

        return $this->sbSuggestions;

    }




}
