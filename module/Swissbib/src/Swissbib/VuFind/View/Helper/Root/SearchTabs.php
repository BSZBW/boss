<?php
/**
 * [...description of the type ...]
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 9/12/13
 * Time: 11:46 AM
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
 * @category swissbib_VuFind2
 * @package  [...package name...]
 * @author   Maechler Markus
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace Swissbib\VuFind\View\Helper\Root;

use VuFind\View\Helper\Root\SearchTabs as VuFindSearchTabs;

/**
 * Authentication view helper
 *
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class SearchTabs extends VuFindSearchTabs
{

    /**
     * @param string $activeSearchClass The search class ID of the active search
     * @param string $query             The current search query
     * @param string $handler           The current search handler
     * @param string $type              The current search type (basic/advanced)
     * @param string $view              variable to determine which tab config should be used
     *
     * @return array
     */
    public function __invoke($activeSearchClass, $query, $handler, $type = 'basic', $view = 'default')
    {
        $this->config = $this->injectViewDependentConfig($view);

        return parent::__invoke($activeSearchClass, $query, $handler, $type);
    }

    /**
     * This function is used to distinguish between the two configs [SearchTabs] and [AdvancedSearchTabs]
     * depending on the view parameter
     *
     * @param string $view
     *
     * @return array $config
     */
    public function injectViewDependentConfig($view)
    {
        switch ($view) {
        case 'advanced':
            return array_key_exists('AdvancedSearchTabs', $this->config) ? $this->config['AdvancedSearchTabs'] : array();
        default:
            return array_key_exists('SearchTabs', $this->config) ? $this->config['SearchTabs'] : array();
        }
    }

} 