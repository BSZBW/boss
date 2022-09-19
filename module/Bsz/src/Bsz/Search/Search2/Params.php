<?php
/*
 * Copyright 2022 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

namespace Bsz\Search\Search2;

use Bsz\Config\Dedup;
use DateTime;
use VuFind\Config\PluginManager;
use VuFind\Search\Solr\HierarchicalFacetHelper;

class Params extends \VuFind\Search\Search2\Params
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
        Dedup $dedup = null,
        HierarchicalFacetHelper $facetHelper = null
    ) {
        parent::__construct($options, $configLoader, $facetHelper);
        $this->dedup = $dedup;
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
    /**
     * Format a single filter for use in getFilterList().
     *
     * @param string $field     Field name
     * @param string $value     Field value
     * @param string $operator  Operator (AND/OR/NOT)
     * @param bool   $translate Should we translate the label?
     *
     * @return array
     */
    protected function formatFilterListEntry($field, $value, $operator, $translate)
    {
        $parent = parent::formatFilterListEntry($field, $value, $operator, $translate);

        if ($field == 'publishDateDaySort_date') {
            $string = preg_replace('/T00:00:00Z/', '', $parent['displayText']);
            $from = substr($string, 0, 10);
            $to = substr($string, 11, 10);

            $dateFrom = new DateTime($from);
            $dateTo = new DateTime($to);
            $parent['displayText'] = $dateFrom->format('d.m.Y') . '-' . $dateTo->format('d.m.Y');
        }
        return $parent;
    }


}