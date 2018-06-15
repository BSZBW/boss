<?php
/**
 * Autocomplete handler plugin manager
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @package  Autocomplete
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:autosuggesters Wiki
 */
namespace VuFind\Autocomplete;

/**
 * Autocomplete handler plugin manager
 *
 * @category VuFind
 * @package  Autocomplete
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:autosuggesters Wiki
 */
class PluginManager extends \VuFind\ServiceManager\AbstractPluginManager
{
    /**
     * Default plugin aliases.
     *
     * @var array
     */
    protected $aliases = [
        'none' => 'VuFind\Autocomplete\None',
        'oclcidentities' => 'VuFind\Autocomplete\OCLCIdentities',
        'search2' => 'VuFind\Autocomplete\Search2',
        'search2cn' => 'VuFind\Autocomplete\Search2CN',
        'solr' => 'VuFind\Autocomplete\Solr',
        'solrauth' => 'VuFind\Autocomplete\SolrAuth',
        'solrcn' => 'VuFind\Autocomplete\SolrCN',
        'solrreserves' => 'VuFind\Autocomplete\SolrReserves',
        'tag' => 'VuFind\Autocomplete\Tag',
        // for legacy 1.x compatibility
        'noautocomplete' => 'None',
        'oclcidentitiesautocomplete' => 'OCLCIdentities',
        'solrautocomplete' => 'Solr',
        'solrauthautocomplete' => 'SolrAuth',
        'solrcnautocomplete' => 'SolrCN',
        'solrreservesautocomplete' => 'SolrReserves',
        'tagautocomplete' => 'Tag',
    ];

    /**
     * Default plugin factories.
     *
     * @var array
     */
    protected $factories = [
        'VuFind\Autocomplete\None' => 'Zend\ServiceManager\Factory\InvokableFactory',
        'VuFind\Autocomplete\OCLCIdentities' =>
        'Zend\ServiceManager\Factory\InvokableFactory',
        'VuFind\Autocomplete\Search2' => 'VuFind\Autocomplete\SolrFactory',
        'VuFind\Autocomplete\Search2CN' => 'VuFind\Autocomplete\SolrFactory',
        'VuFind\Autocomplete\Solr' => 'VuFind\Autocomplete\SolrFactory',
        'VuFind\Autocomplete\SolrAuth' => 'VuFind\Autocomplete\SolrFactory',
        'VuFind\Autocomplete\SolrCN' => 'VuFind\Autocomplete\SolrFactory',
        'VuFind\Autocomplete\SolrReserves' => 'VuFind\Autocomplete\SolrFactory',
        'VuFind\Autocomplete\Tag' => 'Zend\ServiceManager\Factory\InvokableFactory',
    ];

    /**
     * Constructor
     *
     * Make sure plugins are properly initialized.
     *
     * @param mixed $configOrContainerInstance Configuration or container instance
     * @param array $v3config                  If $configOrContainerInstance is a
     * container, this value will be passed to the parent constructor.
     */
    public function __construct($configOrContainerInstance = null,
        array $v3config = []
    ) {
        $this->addAbstractFactory('VuFind\Autocomplete\PluginFactory');
        parent::__construct($configOrContainerInstance, $v3config);
    }

    /**
     * Return the name of the base class or interface that plug-ins must conform
     * to.
     *
     * @return string
     */
    protected function getExpectedInterface()
    {
        return 'VuFind\Autocomplete\AutocompleteInterface';
    }

    /**
     * This returns an array of suggestions based on current request parameters.
     * This logic is present in the factory class so that it can be easily shared
     * by multiple AJAX handlers.
     *
     * @param \Zend\Stdlib\Parameters $request    The user request
     * @param string                  $typeParam  Request parameter containing search
     * type
     * @param string                  $queryParam Request parameter containing query
     * string
     *
     * @return array
     */
    public function getSuggestions($request, $typeParam = 'type', $queryParam = 'q')
    {
        // Process incoming parameters:
        $type = $request->get($typeParam, '');
        $query = $request->get($queryParam, '');
        $searcher = $request->get('searcher', 'Solr');
        $hiddenFilters = $request->get('hiddenFilters', []);

        // If we're using a combined search box, we need to override the searcher
        // and type settings.
        if (substr($type, 0, 7) == 'VuFind:') {
            list(, $tmp) = explode(':', $type, 2);
            list($searcher, $type) = explode('|', $tmp, 2);
        }

        // get Autocomplete_Type config
        $options = $this->getServiceLocator()
            ->get('VuFind\SearchOptionsPluginManager')->get($searcher);
        $config = $this->getServiceLocator()->get('VuFind\Config')
            ->get($options->getSearchIni());
        $types = isset($config->Autocomplete_Types) ?
            $config->Autocomplete_Types->toArray() : [];

        // Figure out which handler to use:
        if (!empty($type) && isset($types[$type])) {
            $module = $types[$type];
        } elseif (isset($config->Autocomplete->default_handler)) {
            $module = $config->Autocomplete->default_handler;
        } else {
            $module = false;
        }

        // Get suggestions:
        if ($module) {
            if (strpos($module, ':') === false) {
                $module .= ':'; // force colon to avoid warning in explode below
            }
            list($name, $params) = explode(':', $module, 2);
            $handler = $this->get($name);
            $handler->setConfig($params);
        }

        if (is_callable([$handler, 'addFilters'])) {
            $handler->addFilters($hiddenFilters);
        }

        return (isset($handler) && is_object($handler))
            ? array_values($handler->getSuggestions($query)) : [];
    }
}
