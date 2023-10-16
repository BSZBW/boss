<?php

namespace BszCommon\Search\SearchN;

use VuFind\Record\Loader;
use VuFind\Search\Base\Params;
use VuFindSearch\Service as SearchService;

class Results extends \VuFind\Search\Solr\Results
{
    public function __construct(Params $params, SearchService $searchService, Loader $recordLoader, String $backendId)
    {
        parent::__construct($params, $searchService, $recordLoader);
        $this->backendId = $backendId;
    }
}