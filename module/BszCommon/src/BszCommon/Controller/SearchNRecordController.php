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

namespace BszCommon\Controller;

use Zend\ServiceManager\ServiceLocatorInterface;

class SearchNRecordController extends \VuFind\Controller\AbstractRecord
{

    public function __construct(
        ServiceLocatorInterface $sm,
        string $searchClassId
    ) {
        $this->searchClassId = $searchClassId;
        $this->fallbackDefaultTab = 'Description';
        parent::__construct($sm);
    }

    protected function resultScrollerActive()
    {
        $config = $this->serviceLocator->get(\VuFind\Config::class)
            ->get($this->searchClassId);
        return isset($config->Record->next_prev_navigation)
            && $config->Record->next_prev_navigation;
    }

}