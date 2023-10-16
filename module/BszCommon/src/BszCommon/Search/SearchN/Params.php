<?php

namespace BszCommon\Search\SearchN;

class Params extends \VuFind\Search\Solr\Params
{

    /**
     * Config sections to search for facet labels if no override configuration
     * is set.
     *
     * @var array
     */
    protected $defaultFacetLabelSections = [
        'Advanced_Facets', 'HomePage_Facets', 'ResultsTop', 'Results',
        'ExtraFacetLabels'
    ];

    /**
     * Initialize facet settings for the advanced search screen.
     *
     * @return void
     */
    public function initAdvancedFacets()
    {
        $this->initFacetList('Advanced_Facets', 'Advanced_Settings');
    }

    /**
     * Initialize facet settings for the home page.
     *
     * @return void
     */
    public function initHomePageFacets()
    {
        // Load Advanced settings if HomePage settings are missing (legacy support):
        if (!$this->initFacetList('HomePage_Facets', 'HomePage_Facet_Settings')) {
            $this->initAdvancedFacets();
        }
    }

}