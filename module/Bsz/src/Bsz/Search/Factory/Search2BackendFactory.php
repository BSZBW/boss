<?php

namespace Bsz\Search\Factory;

use Bsz\Backend\Solr\Response\Json\RecordCollectionFactory;
use VuFindSearch\Backend\Solr\Backend;
use VuFindSearch\Backend\Solr\Connector;

class Search2BackendFactory extends SolrDefaultBackendFactory
{

    public function __construct()
    {
        parent::__construct();
        $this->mainConfig = $this->searchConfig = $this->facetConfig = 'Search2';
        $this->searchYaml = 'searchspecs2.yaml';
    }


    /**
     * Get the Solr core.
     *
     * @return string
     */
    protected function getIndexName()
    {
        $config = $this->config->get($this->mainConfig);

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
    protected function createBackend(Connector $connector)
    {
        $backend = parent::createBackend($connector);
        $manager = $this->serviceLocator->get('VuFind\RecordDriverPluginManager');

        $factory = new RecordCollectionFactory([$manager, 'getSolrRecord']);
        $backend->setRecordCollectionFactory($factory);
        return $backend;
    }

}