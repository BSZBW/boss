<?php
namespace Swissbib\VuFind\Search\SolrClassification;

use Swissbib\VuFind\Search\Solr\Options as SwissbibSolrOptions;

class Options extends SwissbibSolrOptions
{
    public function getAdvancedSearchAction()
    {
        return 'search-advancedClassification';
    }
}
