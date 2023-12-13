<?php
/**
 * finc specific model for Solr records based on the stock
 * VuFind\RecordDriver\SolrDefault
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2015.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace Finc\RecordDriver;

use VuFind\Log\LoggerAwareTrait;
use VuFind\Search\SearchRunner;
use Laminas\Config\Config;
use Laminas\Log\LoggerAwareInterface as LoggerAwareInterface;

/**
 * finc specific model for Solr records based on the stock
 * VuFind\RecordDriver\SolrDefault
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @author   Frank Morgner <morgnerf@ub.uni-leipzig.de>
 * @author   Ulf Seltmann <seltmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class SolrDefault extends \VuFind\RecordDriver\SolrDefault implements
    LoggerAwareInterface
{
    use LoggerAwareTrait;
    use SolrDefaultFincTrait;

    /**
     * Index extension used for dynamic fields
     *
     * @var string
     */
    protected $indexExtension = '';

    /**
     * Constructor
     *
     * @param Config $mainConfig     VuFind main configuration (omit for
     * built-in defaults)
     * @param Config $recordConfig   Record-specific configuration file
     * (omit to use $mainConfig as $recordConfig)
     * @param Config $searchSettings Search-specific configuration file
     */
    public function __construct(Config $mainConfig = null, Config $recordConfig = null,
                                Config $searchSettings = null
    ) {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);

        if (isset($this->mainConfig->CustomIndex->indexExtension)) {
            $this->indexExtension = $this->mainConfig->CustomIndex->indexExtension;
        } else {
            $this->debug('Index extension for custom index not set!');
        }
    }

    /**
     * Return value of the requestes field, null if field ist not set.
     *
     * @param string $field Name of the field.
     *
     * @return mixed
     */
//    public function getField($field)
//    {
//        if (isset($this->fields[$field])) {
//            return $this->fields[$field];
//        }
//        return null;
//    }

    /**
     * Helper function to restructure author arrays including relators
     *
     * @param array $authors Array of authors
     * @param array $roles   Array with relators of authors
     *
     * @return array
     */
    protected function getAuthorRolesArray($authors = [], $roles = [])
    {
        $authorRolesArray = [];

        if (!empty($authors)) {
            foreach ($authors as $index => $author) {
                if (!isset($authorRolesArray[$author])) {
                    $authorRolesArray[$author] = [];
                }
                if (isset($roles[$index]) && !empty($roles[$index])
                ) {
                    $authorRolesArray[$author][] = $roles[$index];
                }
            }
        }

        return $authorRolesArray;
    }

    /**
     * Get the Consortium from a record.
     *
     * @return array
     */
    public function getConsortium()
    {
    }

    /**
     * Not implemented in this module
     *
     * @param string $field Name of the field.
     *
     * @return array
     */
    public function getField924()
    {
        return [];
    }

    /**
     * Attach a Search Results Plugin Manager connection and related logic to
     * the driver
     *
     * @param \VuFind\SearchRunner $runner
     * @return void
     */
    public function attachSearchRunner(SearchRunner $runner)
    {
        $this->runner = $runner;
    }

    /**
     * Not implemented in this module
     *
     * @param string $field Name of the field.
     *
     * @return mixed
     */
    public function getSubRecords()
    {
        return null;
    }

    /**
     * Not implemented in this module
     *
     * @param string $field Name of the field.
     *
     * @return mixed
     */
    public function hasSubRecords()
    {
        return null;
    }

    /**
     * Not implemented in this module
     *
     * @param string $field Name of the field.
     *
     * @return mixed
     */
    public function isSubRecord()
    {
        return null;
    }

    /**
     * Not implemented in this module
     *
     * @param string $field Name of the field.
     *
     * @return mixed
     */
    public function getContainerId()
    {
        return null;
    }

    /**
     * Not implemented in this module
     *
     * @param string $field Name of the field.
     *
     * @return mixed
     */
    public function getContainerPages()
    {
        return null;
    }

    /**
     * Not implemented in this module
     *
     * @param string $field Name of the field.
     *
     * @return mixed
     */
    public function getContainerRelParts()
    {
        return null;
    }

    /**
     * Not implemented in this module
     *
     * @param string $field Name of the field.
     *
     * @return mixed
     */
    public function getContainerYear()
    {
        return null;
    }

    /**
     * Not implemented in this module
     *
     * @param string $field Name of the field.
     *
     * @return mixed
     */
    public function isArticle()
    {
        return null;
    }

    /**
     * Not implemented in this module
     *
     * @param string $field Name of the field.
     *
     * @return mixed
     */
    public function getSeriesIds()
    {
        return null;
    }

    /**
     * Get an array of notes "Enthaltene Werke" for the Notes-Tab.
     *
     * @return array
     */
    public function getMusicalCast()
    {
        return null;
    }
}
