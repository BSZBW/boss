<?php

/**
 * Description of Backend
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
namespace Bsz\Backend\EDS;

use Exception;
use VuFindSearch\Backend\EDS\Laminas2 as ApiClient;
use VuFindSearch\Response\RecordCollectionFactoryInterface;
use Laminas\Cache\Storage\Adapter\AbstractAdapter as CacheAdapter;
use Laminas\Config\Config;
use Laminas\Session\Container as SessionContainer;

class Backend extends \VuFindSearch\Backend\EDS\Backend
{
    /**
     * List of allowed IPs
     *
     * @var string
     */
    protected $localips = '';

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
        // Save dependencies/incoming parameters:
        $this->client = $client;
        $this->setRecordCollectionFactory($factory);
        $this->cache = $cache;
        $this->session = $session;
        $this->isGuest = $isGuest;
        // Extract key values from configuration:
        if (isset($config->EBSCO_Account->user_name)) {
            $this->userName = $config->EBSCO_Account->user_name;
        }
        if (isset($config->EBSCO_Account->password)) {
            $this->password = $config->EBSCO_Account->password;
        }
        if (isset($config->EBSCO_Account->ip_auth)) {
            $this->ipAuth = $config->EBSCO_Account->ip_auth;
        }
        if (isset($config->EBSCO_Account->profile)) {
            $this->profile = $config->EBSCO_Account->profile;
        }
        if (isset($config->EBSCO_Account->organization_id)) {
            $this->orgId = $config->EBSCO_Account->organization_id;
        }
        if (isset($config->EBSCO_Account->local_ip_addresses)) {
            $this->localips = $config->EBSCO_Account->local_ip_addresses;
        }
        // Save default profile value, since profile property may be overriden:
        $this->defaultProfile = $this->profile;
    }

    protected function isAuthenticationIP()
    {
        $this->debugPrint("isAuthenticationIP-0 : " . $this->localips);
        $res = $this->validAuthIP($this->localips);
        $this->debugPrint("isAuthenticationIP-1 : " . $res);
        return $res;
    }

    protected function isGuest()
    {
        // If the user is not logged in, then treat them as a guest. Unless they are
        // using IP Authentication.
        // If IP Authentication is used, then don't treat them as a guest.

        if ($this->isAuthenticationIP()) {
            return 'n';
        }
        if (isset($this->authManager)) {
            return $this->authManager->isLoggedIn() ? 'n' : 'y';
        }
        return 'y';
    }

    /**
     * Determines whether or not the current user session is identifed as a guest
     * session
     *
     * @return string 'y'|'n'
     */
    protected function validAuthIP($listIPs)
    {
        try {
            if ($listIPs == '') {
                return false;
            }
            $m = explode(',', $listIPs);
            if (count($m) == 0) {
                return false;
            }
            // get the ip address of the request
            $remote = new \Laminas\Http\PhpEnvironment\RemoteAddress;
            $ip_address = $remote->getIpAddress();

            foreach ($m as $ip) {
                $ip = trim($ip);
                if (strpos($ip, '/') !== false) {
                    list($network, $cidr) = explode('/', $ip);
                    $actual = ip2long($ip_address) & ~((1 << (32 - $cidr)) - 1);
                    $longNet = ip2long($network);
                    if ($actual == $longNet) {
                        return true;
                    }
                } elseif ($ip_address == $ip) {
                    return true;
                }
            }
        } catch (Exception $e) {
            $this->debugPrint("validAuthIP ex: " . $e);
        }
        return false;
    }
}
