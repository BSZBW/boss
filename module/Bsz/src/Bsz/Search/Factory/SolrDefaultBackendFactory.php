<?php

/**
 * Factory for the default SOLR backend.
 *
 * @category Ida
 * @package  Search
 * @author   <dku@outermedia.de>
 */
namespace Bsz\Search\Factory;

use Bsz\Backend\Solr\Response\Json\RecordCollectionFactory;
use VuFindSearch\Backend\Solr\Connector;
use VuFindSearch\Backend\Solr\Backend;

class SolrDefaultBackendFactory extends AbstractSolrBackendFactory {

    /**
     * Constructor
     */
    public function __construct() {

        parent::__construct();
        $this->searchConfig = 'searches';
        $this->searchYaml = 'searchspecs.yaml';
    }

    /**
     * Get the Solr core.
     *
     * @return string
     */
    protected function getSolrCore() {

        $config = $this->config->get('config');

        return isset($config->Index->default_core)
            ? $config->Index->default_core : 'biblio';
    }

    /**
     * Create the SOLR backend.
     *
     * @param Connector $connector Connector
     *
     * @return Backend
     */
    protected function createBackend(Connector $connector) {

        $backend = parent::createBackend($connector);
        $manager = $this->serviceLocator->get('VuFind\RecordDriverPluginManager');

        $factory = new RecordCollectionFactory(array($manager, 'getSolrRecord'));
        $backend->setRecordCollectionFactory($factory);

        return $backend;
    }
}