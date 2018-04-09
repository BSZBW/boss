<?php

/**
 * Abstract factory for SOLR backends.
 *
 * @category Ida
 * @package  Search
 * @author   <dku@outermedia.de>
 */

namespace Bsz\Search\Factory;

use VuFindSearch\Backend\Solr\Connector;
use Bsz\Backend\Solr\Backend;

abstract class AbstractSolrBackendFactory extends \VuFind\Search\Factory\AbstractSolrBackendFactory {

    /**
     * Create the SOLR backend.
     *
     * @param Connector $connector Connector
     *
     * @return Backend
     */
    protected function createBackend(Connector $connector) {

        $backend = new Backend($connector);
        $backend->setQueryBuilder($this->createQueryBuilder());

        if ($this->logger) {

            $backend->setLogger($this->logger);
        }

        return $backend;
    }
}
