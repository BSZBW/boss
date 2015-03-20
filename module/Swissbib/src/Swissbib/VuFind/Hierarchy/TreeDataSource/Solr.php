<?php
/**
 * Hierarchy Tree Data Source (Solr)
 *
 * PHP version 5
 *
 * Copyright (C) Basel University Library, project swissbib
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  HierarchyTree_DataSource
 * @author   Oliver Schihin <oliver.schihin@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:hierarchy_components Wiki
 * @link     http://www.swissbib.org
 */
namespace Swissbib\VuFind\Hierarchy\TreeDataSource;

use VuFind\Hierarchy\TreeDataSource\Solr as VuFindTreeDataSourceSolr;
use VuFindSearch\Query\Query;
use VuFindSearch\Service as SearchService;
use VuFindSearch\ParamBag;

/**
 * Override Solr tree data source
 *
 */
class Solr extends VuFindTreeDataSourceSolr
{
    /**
     * Search service
     *
     * @var SearchService
     */
    protected $searchService;

    /**
     * Cache directory
     *
     * @var string
     */
    protected $cacheDir = null;

    /**
     * Filter queries
     *
     * @var array
     */
    protected $filters = array();


    /**
     * Get Solr Children for JSON
     *
     * @param string $parentID The starting point for the current recursion
     * (equivlent to Solr field hierarchy_parent_id)
     * @param string &$count   The total count of items in the tree
     * before this recursion
     *
     * @return string
     */
    protected function getChildrenJson($parentID, &$count)
    {
        $query = new Query(
            'hierarchy_parent_id:"' . addcslashes($parentID, '"') . '"'
        );
        $results = $this->searchService->search(
            'Solr', $query, 0, 10000,
            new ParamBag(array('fq' => $this->filters, 'hl' => 'false', 'fl' => 'id, title_in_hierarchy, hierarchy_parent_id, hierarchy_sequence, hierarchy_top_title, title'))
        );
        if ($results->getTotal() < 1) {
            return '';
        }
        $json = array();
        $sorting = $this->getHierarchyDriver()->treeSorting();

        foreach ($results->getRecords() as $current) {
            ++$count;

            $titles = $current->getTitlesInHierarchy();
            $title = isset($titles[$parentID])
                ? $titles[$parentID] : $current->getTitle();

            $this->debug("$parentID: " . $current->getUniqueID());
            $childNode = array(
                'id' => $current->getUniqueID(),
                'type' => $current->isCollection()
                    ? 'collection'
                    : 'record',
                'title' => htmlspecialchars($title)
            );
            // here, the logic seems to have changed with respect to ::getChildren (creating xml caches). Beforehand, the
            // building of subchildren were not dependent on the type collection=true/false
            // commentend this out to get old behaviour
            //if ($current->isCollection()) {
            $children = $this->getChildrenJson(
                $current->getUniqueID(),
                $count
                );
            if (!empty($children)) {
                $childNode['children'] = $children;
                }
            //}

            // If we're in sorting mode, we need to create key-value arrays;
            // otherwise, we can just collect flat values.
            if ($sorting) {
                $positions = $current->getHierarchyPositionsInParents();
                $sequence = isset($positions[$parentID]) ? $positions[$parentID] : 0;
                $json[] = array($sequence, $childNode);
            } else {
                $json[] = $childNode;
            }
        }

        return $sorting ? $this->sortNodes($json) : $json;
    }



    /**
     * Sort Nodes, special sort for Swissbib purposes
     *
     * @param array  &$array The Array to Sort
     * @param string $key    The key to sort on
     *
     * @return void
     */
    protected function sortNodes($array) {

         $sorter = function ($a, $b) {
             // consider first element for the sort: $a[0]
             if (preg_match("/^(\d+)(\D+.*)?$/", $a[0], $allMatches)) {
                 if (sizeof($allMatches) == 3) {
                     $first = $allMatches[1] . "." . preg_replace("/\D/", "", ($allMatches[2]));
                 } else {
                     $first = $allMatches[1];
                 }
             } else {
                 $first = '0'; // there is no numeric value to compare with
             }
             // consider first element for the sort: $b[0]
             if (preg_match("/^(\d+)(\D+.*)?$/", $b[0], $allMatches)) {
                 if (sizeof($allMatches) == 3) {
                     $second = $allMatches[1] . "." . preg_replace("/\D/", "", ($allMatches[2]));
                 } else {
                     $second = $allMatches[1];
                 }
             } else {
                 $second = '0'; // there is no numeric value to compare with
             }

             // Sort arrays with precision of up to 6 decimals
             return $first === $second ? 0 : $first < $second ? -1 : 1;
             //don't use bccomp. needs a special compiler configuration for PHP (works on Ubuntu but not on RedHat host (PHP version 5.4 as well as 5.5)
             //PHP 5.5 was tested by myself on sb-vf16
             //return bccomp($first, $second, 6);
       };
        usort($array, $sorter);

        // Collapse array to remove sort values
        $mapper = function ($i) {
            return $i[1];
        };
        return array_map($mapper, $array);
    }


}
