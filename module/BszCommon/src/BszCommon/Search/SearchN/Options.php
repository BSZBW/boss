<?php

namespace BszCommon\Search\SearchN;

use VuFind\XSLT\Import\VuFind;

class Options extends \VuFind\Search\Solr\Options
{
    private $searchClassId;

    public function __construct(\VuFind\Config\PluginManager $configLoader, string $searchClassId)
    {
        $this->mainIni = $this->searchIni = $this->facetsIni = $searchClassId;
        $this->searchClassId = strtolower($searchClassId);
        parent::__construct($configLoader);
    }

    /**
     * Return the route name for the facet list action. Returns false to cover
     * unimplemented support.
     *
     * @return string|bool
     */
    public function getFacetListAction()
    {
        return $this->searchClassId . '-facetlist';
    }

    /**
     * Return the route name for the search results action.
     *
     * @return string
     */
    public function getSearchAction()
    {
        return $this->searchClassId . '-results';
    }

    /**
     * Return the route name of the action used for performing advanced searches.
     * Returns false if the feature is not supported.
     *
     * @return string|bool
     */
    public function getAdvancedSearchAction()
    {
        return $this->searchClassId . '-advanced';
    }

    public function getSearchClassId()
    {
        return $this->searchClassId;
    }

}