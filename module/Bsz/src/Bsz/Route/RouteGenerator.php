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
namespace Bsz\Route;

/**
 * Description of RouteGenerator
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class RouteGenerator extends \VuFind\Route\RouteGenerator
{
    /**
     * Constructor
     *
     * @param array $nonTabRecordActions List of non-tab record actions (null
     * for default).
     */
    public function __construct(array $nonTabRecordActions = null)
    {
        if (null === $nonTabRecordActions) {
            self::$nonTabRecordActions = [
                'AddComment', 'DeleteComment', 'AddTag', 'DeleteTag', 'Save',
                'Email', 'SMS', 'Cite', 'Export', 'RDF', 'Hold', 'BlockedHold',
                'Home', 'StorageRetrievalRequest', 'AjaxTab',
                'BlockedStorageRetrievalRequest', 'ILLRequest', 'BlockedILLRequest',
                'PDF', 'ILLForm'
            ];
        } else {
            self::$nonTabRecordActions = $nonTabRecordActions;
        }
    }
}
