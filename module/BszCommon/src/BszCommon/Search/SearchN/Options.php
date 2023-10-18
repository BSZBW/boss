<?php

/*
 * Copyright (C) 2023 Bibliotheks-Service Zentrum, Konstanz, Germany
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
 */

namespace BszCommon\Search\SearchN;

class Options extends \VuFind\Search\Solr\Options
{
    private $searchClassId;

    public function __construct(
        \VuFind\Config\PluginManager $configLoader,
        string $searchClassId
    ) {
        $this->mainIni = $this->searchIni = $this->facetsIni = $searchClassId;
        $this->searchClassId = $searchClassId;
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
        return strtolower($this->searchClassId) . '-facetlist';
    }

    /**
     * Return the route name for the search results action.
     *
     * @return string
     */
    public function getSearchAction()
    {
        return strtolower($this->searchClassId) . '-results';
    }

    /**
     * Return the route name of the action used for performing advanced searches.
     * Returns false if the feature is not supported.
     *
     * @return string|bool
     */
    public function getAdvancedSearchAction()
    {
        return strtolower($this->searchClassId) . '-advanced';
    }

    public function getSearchClassId()
    {
        return $this->searchClassId;
    }
}
