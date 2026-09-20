<?php
/**
 * Recorddriver for Solr records with intermediate schema in field fullrecord in
 * index of Leipzig University Library
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2017.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace Finc\RecordDriver;

/**
 * Recorddriver for Solr records with intermediate schema in field fullrecord in
 * index of Leipzig University Library
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrIS extends SolrAI
{
    /**
     * Returns the IS fullrecord as decoded json.
     *
     * @param string $id Record id to be retrieved.
     *
     * @return array
     */
    protected function getAIJSONFullrecord($id)
    {
        return json_decode($this->fields['fullrecord'], true);
    }
}
