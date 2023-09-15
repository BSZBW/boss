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

namespace Bsz\RecordTab;

use VuFindSearch\ParamBag;
use VuFindSearch\Query\Query;
use VuFindSearch\Service as SearchService;

abstract class AbstractCollection extends \BszCommon\RecordTab\AbstractCollection
{

    /**
     * @var array
     */
    protected $isils;

    public function __construct(SearchService $search, array $isils)
    {
        parent::__construct($search);
        $this->isils = $isils;
    }

    protected function getQuery(): Query
    {
        $id = $this->driver->getUniqueID();
        return new Query('id_related:"' . $id . '"');
    }

    protected function getParams(): ParamBag
    {
        $filterOr = [];
        if ($this->isInterlending() === false) {
            foreach ($this->isils as $isil) {
                $filterOr[] = 'institution_id:' . $isil;
            }
        }
        $params = new ParamBag();
        $params->add('fq', implode(' OR ', $filterOr));

        //$params->add('sort', 'publish_date_sort desc');
        $params->add('hl', 'false');
        $params->add('echoParams', 'ALL');

        return $params;
    }


    /**
     * Check if we are in an interlending or ZDB-TAB
     *
     * @return bool
     * **/
    private function isInterlending(): bool
    {
        $last = '';
        if (isset($_SESSION['Search']['last'])) {
            $last = urldecode($_SESSION['Search']['last']);
        }
        if (strpos($last, 'consortium:FL') !== false
            || strpos($last, 'consortium:"FL"') !== false
            || strpos($last, 'consortium:ZDB') !== false
            || strpos($last, 'consortium:"ZDB"') !== false
        ) {
            return true;
        } else {
            return false;
        }
    }

}