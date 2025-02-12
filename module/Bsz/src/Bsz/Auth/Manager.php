<?php
namespace Bsz\Auth;

/**
 * BSZ variant of AuthManager
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
use Bsz\Config\Library;
use VuFind\Auth\LoginTokenManager;
use VuFind\Auth\PluginManager;
use VuFind\Auth\UserSessionPersistenceInterface;
use VuFind\Cookie\CookieManager;
use VuFind\Db\Service\UserServiceInterface;
use VuFind\Db\Table\User as UserTable;
use Laminas\Config\Config;
use Laminas\Session\SessionManager;
use Laminas\Validator\Csrf;
use VuFind\ILS\Connection;
use VuFind\Validator\CsrfInterface;

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
     * @param Config                          $config            VuFind configuration
     * @param UserServiceInterface            $userService       User database service
     * @param UserSessionPersistenceInterface $userSession       User session persistence service
     * @param SessionManager                  $sessionManager    Session manager
     * @param PluginManager                   $pluginManager     Authentication plugin manager
     * @param CookieManager                   $cookieManager     Cookie manager
     * @param CsrfInterface                   $csrf              CSRF validator
     * @param LoginTokenManager               $loginTokenManager Login Token manager
     * @param Connection                      $ils               ILS connection
     */
    public function __construct(
        protected Config $config,
        protected UserServiceInterface $userService,
        protected UserSessionPersistenceInterface $userSession,
        protected SessionManager $sessionManager,
        protected PluginManager $pluginManager,
        protected CookieManager $cookieManager,
        protected CsrfInterface $csrf,
        protected LoginTokenManager $loginTokenManager,
        protected Connection $ils,
        Library $library = null
    ) {
        parent::__construct(
            $config, $userService, $userSession,
            $sessionManager, $pluginManager, $cookieManager,
            $csrf, $loginTokenManager, $ils
        );
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
