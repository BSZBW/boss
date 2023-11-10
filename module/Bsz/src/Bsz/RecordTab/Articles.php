<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
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
use VuFindSearch\Service as SearchService;

/**
 * Class Articles
 * @package Bsz\RecordTab
 * @category boss
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Articles extends AbstractCollection
{

    /**
     * @var string
     */
    protected $searchClassId;

    /**
     * Constructor
     * @param SearchService $search
     * @param array $isils
     */
    public function __construct(SearchService $search, array $isils = [])
    {
        parent::__construct($search, $isils);
        $this->accessPermission = 'access.ArticlesViewTab';
    }

    /**
     * Get the on-screen description for this tab
     * @return string
     */
    public function getDescription()
    {
        return 'Articles';
    }

    protected function display($record): bool
    {
        $id = $this->driver->getUniqueID();
        $fields = $record->getAllFieldsArray([773 => ['w'], 800 => ['w']]);
        return in_array($id, $fields) && $record->isArticle();
    }

}
