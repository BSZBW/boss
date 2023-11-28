<?php

/**
 * Description of Backend
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
namespace Bsz\Backend\EDS;

use Exception;
use VuFindSearch\Backend\EDS\Connector as ApiClient;
use VuFindSearch\Response\RecordCollectionFactoryInterface;
use Laminas\Cache\Storage\Adapter\AbstractAdapter as CacheAdapter;
use Laminas\Config\Config;
use Laminas\Session\Container as SessionContainer;

class Backend extends \VuFindSearch\Backend\EDS\Backend
{

    /**
     * Constructor.
     *
     * @param ApiClient                        $client  EdsApi client to use
     * @param RecordCollectionFactoryInterface $factory Record collection factory
     * @param CacheAdapter                     $cache   Object cache
     * @param SessionContainer                 $session Session container
     * @param Config                           $config  Object representing EDS.ini
     * @param bool                             $isGuest Is the current user a guest?
     */
    public function __construct(ApiClient $client,
        RecordCollectionFactoryInterface $factory, CacheAdapter $cache,
        SessionContainer $session, Config $config = null, $isGuest = true
    ) {

        parent::__construct($client, $factory, $cache, $session);
//        $this->isGuest = $isGuest;
//
//        // Extract key values from configuration:
//        $this->userName = $config->EBSCO_Account->user_name ?? null;
//        $this->password = $config->EBSCO_Account->password ?? null;
//        $this->ipAuth = $config->EBSCO_Account->ip_auth ?? false;
//        $this->profile = $config->EBSCO_Account->profile ?? null;
//        $this->orgId = $config->EBSCO_Account->organization_id ?? null;
//
//        // Save default profile value, since profile property may be overriden:
//        $this->defaultProfile = $this->profile;
    }

}
