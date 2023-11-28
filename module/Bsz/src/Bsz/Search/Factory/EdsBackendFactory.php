<?php

/**
 * Factory for the default SOLR backend.
 *
 * @category Ida
 * @package  Search
 * @author   <dku@outermedia.de>
 */
namespace Bsz\Search\Factory;

use Bsz\Backend\EDS\Backend;
use VuFindSearch\Backend\EDS\Connector;

class EdsBackendFactory extends \VuFind\Search\Factory\EdsBackendFactory
{
    /**
     * Create the EDS backend.
     *
     * @param Connector $connector Connector
     *
     * @return Backend
     */
    protected function createBackend(Connector $connector)
    {
        $auth = $this->serviceLocator->get('LmcRbacMvc\Service\AuthorizationService');
        $isGuest = !$auth->isGranted('access.EDSExtendedResults');
        $session = new \Laminas\Session\Container(
            'EBSCO', $this->serviceLocator->get('VuFind\SessionManager')
        );
        $backend = new Backend(
            $connector, $this->createRecordCollectionFactory(),
            $this->serviceLocator->get('VuFind\CacheManager')->getCache('object'),
            $session, $this->edsConfig, $isGuest
        );
        $backend->setAuthManager($this->serviceLocator->get('VuFind\AuthManager'));
        $backend->setLogger($this->logger);
        $backend->setQueryBuilder($this->createQueryBuilder());
        $backend->setBackendType($this->getServiceName());
        return $backend;
    }
}
