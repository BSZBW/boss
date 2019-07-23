    <?php

namespace Bsz\Auth;

/**
 * BSZ variant of AuthManager
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */


use VuFind\Cookie\CookieManager;
use VuFind\Db\Row\User as UserRow;
use VuFind\Db\Table\User as UserTable;
use VuFind\Exception\Auth as AuthException;
use Zend\Config\Config;
use Zend\Session\SessionManager;
use Zend\Validator\Csrf;
use VuFind\Auth\PluginManager;
use Bsz\Config\Library;

/**
 * 
 */
class Manager extends \VuFind\Auth\Manager
    implements \ZfcRbac\Identity\IdentityProviderInterface
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
        if (isset($this->library) && $this->library->getAuth() != 'shibboleth') {            
            return false;
        } else {
        // Assume login is enabled unless explicitly turned off:
        return isset($this->config->Authentication->hideLogin)
            ? !$this->config->Authentication->hideLogin
            : true;           
        }
    }
}
