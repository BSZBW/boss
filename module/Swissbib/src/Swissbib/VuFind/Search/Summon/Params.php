<?php


namespace Swissbib\VuFind\Search\Summon;

use VuFind\Search\Summon\Params as VFSummonParams;
use SerialsSolutions_Summon_Query as SummonQuery,
    VuFind\Solr\Utils as SolrUtils,
    VuFindSearch\ParamBag;


class Params extends VFSummonParams
{

    use \Swissbib\VuFind\Search\Helper\PersonalSettingsHelper;

    /**
     * @var array
     */
    protected $dateRange = array(
        'isActive' => false
    );


    /**
     * Pull the page size parameter or set to default
     *
     * @param \Zend\StdLib\Parameters $request Parameter object representing user
     * request.
     *
     * @return void */
    protected function initLimit($request)
    {

        $auth = $this->serviceLocator->get('VuFind\AuthManager');
        $defLimit = $this->getOptions()->getDefaultLimit();
        $limitOptions = $this->getOptions()->getLimitOptions();
        $view = $this->getView();
        $this->handleLimit($auth, $request,$defLimit, $limitOptions, $view );
    }


    /**
     * Get the value for which type of sorting to use
     *
     * @param \Zend\StdLib\Parameters $request Parameter object representing user
     * request.
     *
     * @return string
     */
    protected function initSort($request)
    {
        $auth = $this->serviceLocator->get('VuFind\AuthManager');
        $defaultSort = $this->getOptions()->getDefaultSortByHandler();

        $this->setSort($this->handleSort($auth,$request,$defaultSort, $this->getSearchClassId()));
    }



    /*
     * GH: we need this method to call initLimit (which is protected in base class and shouldn't be changed only because
     * of hacks relaed to silly personal settings (although is possible in the current PHP version)
     *
     */
    public function initLimitAdvancedSearch($request)
    {
        $this->initLimit($request);
    }


    public function getSearchClassId()
    {



        //$class = explode('\\', get_class($this));
        //return $class[2];
        //we can't use the basic VuFind mechanism return class[2] because our namespace is build as
        //Swissbib/Vufind/Search/[specialized Search target]
        //therefor it has o be $class[3]
        //My guess: the whole Design related to search types will be refactored by VuFind in the upcoming time (More intensive use of EventManager)
        //so return the name of the target makes it more explicit for a type only responsible for Summon results
        return 'Summon';

    }

    /**
     * Set up filters based on VuFind settings.
     *
     * @param ParamBag $params     Parameter collection to update
     *
     * @return void
     */
    public function createBackendFilterParameters(ParamBag $params)
    {
        // flag our non-Standard checkbox filters:
        $foundIncludeNewspapers = false;        # includeNewspapers
        $foundIncludeWithoutFulltext = false;   # includeWithoutFulltext
        $filterList = $this->getFilterList();
        // Which filters should be applied to our query?
        if (!empty($filterList)) {
            // Loop through all filters and add appropriate values to request:
            foreach ($filterList as $filterArray) {
                foreach ($filterArray as $filt) {
                    $safeValue = SummonQuery::escapeParam($filt['value']);
                    if ($filt['field'] == 'holdingsOnly') {
                        // Special case -- "holdings only" is a separate parameter from
                        // other facets.
                        $params->set('holdings', strtolower(trim($safeValue)) == 'true');
                    } else if ($filt['field'] == 'excludeNewspapers') {
                        // support a checkbox for excluding newspapers:
                        // this is now the default behaviour.
                    } else if ($filt['field'] == 'includeNewspapers') {
                        // explicitly include newspaper articles
                        $foundIncludeNewspapers = true;
                    } else if ($range = SolrUtils::parseRange($filt['value'])) {
                        // Special case -- range query (translate [x TO y] syntax):
                        $from = SummonQuery::escapeParam($range['from']);
                        $to = SummonQuery::escapeParam($range['to']);
                        $params->add('rangeFilters', "PublicationDate,{$from}:{$to}");
                    } else if ($filt['field'] == 'includeWithoutFulltext') {
                        $foundIncludeWithoutFulltext = true;
                    } else {
                        // Standard case:
                        $params->add('filters', "{$filt['field']},{$safeValue}");
                    }
                }
            }
        }
        // special cases (apply also when filter list is empty)
        // newspaper articles
        if ( ! $foundIncludeNewspapers ) {
            // this actually means: do *not* show newspaper articles
            $params->add('filters', "ContentType,Newspaper Article,true");
        }
        // combined facet "with holdings/with fulltext"
        if ( !$foundIncludeWithoutFulltext ) {
            $params->set('holdings', true);
            $params->add('filters',  'IsFullText,true');

        } else {
            $params->set('holdings', false);
        }
    }



    /**
     * @return string
     */
    public function getTypeLabel()
    {
        return $this->getServiceLocator()->get('Swissbib\TypeLabelMappingHelper')->getLabel($this);
    }



    /**
     * @return array
     */
    public function getDateRange()
    {
        $this->dateRange['min'] = 1980;
        $this->dateRange['max'] = intval(date('Y')) + 1;

        if (!$this->dateRange['isActive']) {
            $this->dateRange['from']    = (int) $this->dateRange['min'];
            $this->dateRange['to']      = (int) $this->dateRange['max'];
        }

        return $this->dateRange;
    }



    /**
     * @Override
     *
     * @param string $field field to use for filtering.
     * @param string $from  year for start of range.
     * @param string $to    year for end of range.
     *
     * @return string       filter query.
     */
    protected function buildDateRangeFilter($field, $from, $to)
    {
        $this->dateRange['from']        = (int) $from;
        $this->dateRange['to']          = (int) $to;
        $this->dateRange['isActive']    = true;

        return parent::buildDateRangeFilter($field, $from, $to);
    }

    /**
     *
     */
    public function initHomePageFacets()
    {
        // Load Advanced settings if HomePage settings are missing (legacy support):
        if (!$this->initFacetList('HomePage', 'HomePage_Settings', 'Summon')) {
            $this->initAdvancedFacets();
        }
    }
}