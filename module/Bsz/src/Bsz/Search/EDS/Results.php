<?php

namespace Bsz\Search\EDS;

class Results extends \VuFind\Search\EDS\Results
{

    protected function buildFacetList(array $facetList, array $filter = null): array
    {
        $parent = parent::buildFacetList($facetList, $filter);

        $translatedFacets = $this->getOptions()->getTranslatedFacets();
        foreach ($parent as $field => &$facet) {
            foreach ($facet['list'] as &$item) {
                $uc = ucwords($item['displayText']);
                $item['displayText'] = in_array($field, $translatedFacets)
                    ? $this->getParams()->translateFacetValue($field, $uc)
                    : $uc;
            }
        }

        return $parent;
    }
}