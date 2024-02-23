<?php
namespace Bsz\Auth;

/**
 * BSZ variant of AuthManager
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
use Bsz\Config\Library;
use VuFind\Auth\PluginManager;
use VuFind\Cookie\CookieManager;
use VuFind\Db\Table\User as UserTable;
use Laminas\Config\Config;
use Laminas\Session\SessionManager;
use Laminas\Validator\Csrf;

class Manager extends \VuFind\Auth\Manager
{
    /**
     *
     * @var Libraries;
     */
    protected $library;

    /**
     * Constructor
     *
     * @param Config         $config         VuFind configuration
     * @param UserTable      $userTable      User table gateway
     * @param SessionManager $sessionManager Session manager
     * @param PluginManager  $pm             Authentication plugin manager
     * @param CookieManager  $cookieManager  Cookie manager
     */
    public function __construct(Config $config, UserTable $userTable,
        SessionManager $sessionManager, PluginManager $pm,
        CookieManager $cookieManager, Csrf $csrf, Library $library = null
    ) {
        parent::__construct($config, $userTable, $sessionManager, $pm, $cookieManager, $csrf);
        $this->library = $library;
    }

    /**
     * login is shown if selected library has shibboleth auth enabled
     *
     * @return bool
     */
    public function loginEnabled()
    {
        if (isset($this->library) && !$this->library->loginEnabled()) {
            return false;
        } else {
            // Assume login is enabled unless explicitly turned off:
            return isset($this->config->Authentication->hideLogin)
            ? !$this->config->Authentication->hideLogin
            : true;
        }
    }
}
