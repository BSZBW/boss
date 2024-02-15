<?php

namespace BszTheme\View\Helper\Bodensee;

use BszTheme\View\Helper\StringHelper;
use Laminas\View\Helper\AbstractHelper;
use VuFindSearch\Command\SearchCommand;
use VuFindSearch\ParamBag;
use VuFindSearch\Query\Query;
use VuFindSearch\Service as SearchService;

use function Symfony\Component\String\s;

class IdVerifier extends AbstractHelper
{
    private SearchService $searchService;

    public function __construct(SearchService $service)
    {
        $this->searchService = $service;
    }

    public function __invoke(string $id, string $sourceId): bool
    {
        $query = new Query("id: " . $id);
        $command = new SearchCommand($sourceId, $query, 0, 0, null);

        $result = $this->searchService->invoke($command)->getResult();

        return $result->getTotal() > 0;
    }
}