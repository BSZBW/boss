<?php
namespace Bsz\Search\Solr;

use Bsz\Config\Dedup;
use VuFind\Config\PluginManager;
use VuFindSearch\ParamBag;

/**
 * Description of Params
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Params extends \VuFind\Search\Solr\Params
{
    protected $dedup;
    protected $limit = 10;

    /**
     * Params constructor.
     *
     * @param $options
     * @param PluginManager $configLoader
     * @param HierarchicalFacetHelper|null $facetHelper
     * @param Dedup|null $dedup
     */
    public function __construct(
        $options,
        PluginManager $configLoader,
        HierarchicalFacetHelper $facetHelper = null,
        Dedup $dedup = null
    ) {
        parent::__construct($options, $configLoader);
        $this->dedup = $dedup;
    }

    /**
     * Return the current filters as an array of strings ['field:filter']
     *
     * @return array $filterQuery
     */
    public function getFilterSettings()
    {
        // Define Filter Query
        $filterQuery = [];
        $orFilters = [];
        $filterList = array_merge(
            $this->getHiddenFilters(),
            $this->filterList
        );
        foreach ($filterList as $field => $filter) {
            if ($orFacet = (substr($field, 0, 1) == '~')) {
                $field = substr($field, 1);
            }
            if ($filter === '') {
                continue;
            }
            foreach ($filter as $value) {
                // Special case -- complex filter, that should be taken as-is:
                if ($field == '#') {
                    $q = $value;
                } elseif (substr($value, -1) == '*'
                    || preg_match('/\[[^\]]+\s+TO\s+[^\]]+\]/', $value)
                ) {
                    // Special case -- allow trailing wildcards and ranges
                    $q = $field . ':' . $value;
                } else {
                    $q = $field . ':"' . addcslashes($value, '"\\') . '"';
                }
                if ($orFacet) {
                    $orFilters[$field] = $orFilters[$field] ?? [];
                    $orFilters[$field][] = $q;
                } else {
                    $filterQuery[] = $q;
                }
            }
        }
        foreach ($orFilters as $field => $parts) {
            $filterQuery[] = '{!tag=' . $field . '_filter}' . $field
                . ':(' . implode(' OR ', $parts) . ')';
        }
        return $filterQuery;
    }

    /**
     * Create search backend parameters for advanced features.
     *
     * @return ParamBag
     */
    public function getBackendParameters()
    {
        $backendParams = new ParamBag();
        $backendParams->add('year', (int)date('Y') + 1);

        $this->restoreFromCookie();

        // Fetch group params for deduplication
        $config = $this->configLoader->get('config');
        $index = $config->get('Index');
        $group = false;

        $dedupParams = $this->dedup->getCurrentSettings();

        if (isset($dedupParams['group'])) {
            $group = $dedupParams['group'];
        } elseif ($index->get('group') !== null) {
            $group = $index->get('group');
        }

        if ((bool)$group === true) {
            $backendParams->add('group', 'true');

            $group_field = '';
            $group_limit = 0;

            if (isset($dedupParams['group_field'])) {
                $group_field = $dedupParams['group_field'];
            } elseif ($index->get('group.field') !== null) {
                $group_field = $index->get('group.field');
            }
            $backendParams->add('group.field', $group_field);

            if (isset($dedupParams['group_limit'])) {
                $group_limit = $dedupParams['group_limit'];
            } elseif ($index->get('group.limit') !== null) {
                $group_limit = $index->get('group.limit');
            }
            $backendParams->add('group.limit', $group_limit);
        }
        // search those shards that answer, accept partial results
        $backendParams->add('shards.tolerant', 'true');

        // maximum search time in ms
        // $backendParams->add('timeAllowed', '4000');

        // defaultOperator=AND was removed in schema.xml
        $backendParams->add('q.op', "AND");

        // increase performance for facet queries
        $backendParams->add('facet.threads', "4");

        // Spellcheck
        $backendParams->set(
            'spellcheck',
            $this->getOptions()->spellcheckEnabled() ? 'true' : 'false'
        );

        // Facets
        $facets = $this->getFacetSettings();
        if (!empty($facets)) {
            $backendParams->add('facet', 'true');

            foreach ($facets as $key => $value) {
                // prefix keys with "facet" unless they already have a "f." prefix:
                $fullKey = substr($key, 0, 2) == 'f.' ? $key : "facet.$key";
                $backendParams->add($fullKey, $value);
            }
            $backendParams->add('facet.mincount', 1);
        }

        // Filters
        $filters = $this->getFilterSettings();
        foreach ($filters as $filter) {
            $backendParams->add('fq', $filter);
        }

        // Shards
        $allShards = $this->getOptions()->getShards();
        $shards = $this->getSelectedShards();
        if (empty($shards)) {
            $shards = array_keys($allShards);
        }

        // If we have selected shards, we need to format them:
        if (!empty($shards)) {
            $selectedShards = [];
            foreach ($shards as $current) {
                $selectedShards[$current] = $allShards[$current];
            }
            $shards = $selectedShards;
            $backendParams->add('shards', implode(',', $selectedShards));
        }

        // Sort
        $sort = $this->getSort();
        if ($sort) {
            // If we have an empty search with relevance sort, see if there is
            // an override configured:
            if ($sort == 'relevance' && $this->getQuery()->getAllTerms() == ''
                && ($relOv = $this->getOptions()->getEmptySearchRelevanceOverride())
            ) {
                $sort = $relOv;
            }
            $backendParams->add('sort', $this->normalizeSort($sort));
        }

        // Highlighting disabled
        $backendParams->add('hl', 'false');

        // Pivot facets for visual results

        if ($pf = $this->getPivotFacets()) {
            $backendParams->add('facet.pivot', $pf);
        }

        return $backendParams;
    }

    /**
     * This method reads the cookie and stores the information into the session
     * So we only need to process session bwlow.
     *
     */
    protected function restoreFromCookie()
    {
        if (isset($this->cookie)) {
            if (isset($this->cookie->group)) {
                $this->container->offsetSet('group', $this->cookie->group);
            }
            if (isset($this->cookie->group_field)) {
                $this->container->offsetSet('group_field', $this->cookie->group_field);
            }
            if (isset($this->cookie->group_limit)) {
                $this->container->offsetSet('group_limit', $this->cookie->group_limit);
            }
        }
    }

    /**
     * Return current facet configurations
     *
     * @return array $facetSet
     */
    public function getFacetSettings()
    {
        // Build a list of facets we want from the index
        $facetSet = [];
        // List of used prefixes
        $prefixList = [];

        if (!empty($this->facetConfig)) {
            $facetSet['limit'] = $this->facetLimit;
            foreach (array_keys($this->facetConfig) as $facetField) {
                $fieldLimit = $this->getFacetLimitForField($facetField);
                if ($fieldLimit != $this->facetLimit) {
                    $facetSet["f.{$facetField}.facet.limit"] = $fieldLimit;
                }
                $multifieldPrefix = $this->getFacetPrefixForMultiField($facetField);
                if (!empty($multifieldPrefix)) {
                    // &facet.field={!key=fivs facet.prefix=fivs}topic_browse
                    $facetField = '{!key=' . $facetField . ' facet.prefix=' . $facetField . '}' . $multifieldPrefix;
                }

                $fieldPrefix = $this->getFacetPrefixForField($facetField);
                if (!empty($fieldPrefix)) {
                    $facetSet["f.{$facetField}.facet.prefix"] = $fieldPrefix;
                }

                $fieldMatches = $this->getFacetMatchesForField($facetField);
                if (!empty($fieldMatches)) {
                    $facetSet["f.{$facetField}.facet.matches"] = $fieldMatches;
                }

                if ($this->getFacetOperator($facetField) == 'OR') {
                    $facetField = '{!ex=' . $facetField . '_filter}' . $facetField;
                }
                // remove fields of prefixList from solr request
                if ( !in_array($facetField, $prefixList)) {
                    $facetSet['field'][] = $facetField;
                }
            }
            if ($this->facetContains != null) {
                $facetSet['contains'] = $this->facetContains;
            }
            if ($this->facetContainsIgnoreCase != null) {
                $facetSet['contains.ignoreCase']
                    = $this->facetContainsIgnoreCase ? 'true' : 'false';
            }
            if ($this->facetOffset != null) {
                $facetSet['offset'] = $this->facetOffset;
            }
            if ($this->facetPrefix != null) {
                $facetSet['prefix'] = $this->facetPrefix;
            }
            $facetSet['sort'] = $this->facetSort ?: 'count';
            if ($this->indexSortedFacets != null) {
                foreach ($this->indexSortedFacets as $field) {
                    $facetSet["f.{$field}.facet.sort"] = 'index';
                }
            }
        }
        return $facetSet;
    }

}
