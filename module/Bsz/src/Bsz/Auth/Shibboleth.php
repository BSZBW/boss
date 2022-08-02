<?php
namespace Bsz\Auth;

use Bsz\Config\Libraries;
use Bsz\Config\Library;
use VuFind\Exception\Auth as AuthException;

/**
 * Adaptions for our Shibboleth installation
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Shibboleth extends \VuFind\Auth\Shibboleth
{
    protected $libraries;
    protected $isil;

    /**
     * Constructor
     *
     * @param \Laminas\Session\ManagerInterface $sessionManager Session manager
     */
    public function __construct(
        \Laminas\Session\ManagerInterface $sessionManager,
        Libraries $libraries,
        $isil)
    {
        $this->sessionManager = $sessionManager;
        $this->libraries = $libraries;
        $this->isil = $isil;
    }

    /**
     * Attempt to authenticate the current user.  Throws exception if login fails.
     *
     * @param \Laminas\Http\PhpEnvironment\Request $request Request object containing
     * account credentials.
     *
     * @throws AuthException
     * @return \VuFind\Db\Row\User Object representing logged-in user.
     */
    public function authenticate($request)
    {
        $user = parent::authenticate($request);

        if (strpos($user->username, '@') !== false) {
            try {
                $domain = preg_replace('/.+@/', '', $user->username);
                $library = $this->libraries->getByIdPDomain($domain);
                if (isset($library)) {
                    $user->home_library = $library->getIsil();
                    $user->save();
                }
            } catch (\Exception $ex) {
                // in case this does not work - don't worry, user can still manually
                // select library
            }
        }
        return $user;
    }

    /**
     * Perform cleanup at logout time.
     *
     * @param string $url URL to redirect user to after logging out.
     *
     * @return string     Redirect URL (usually same as $url, but modified in
     * some authentication modules).
     */

    public function logout($url)
    {
        $library = $this->libraries->getFirstActive($this->isil);
        $config = $this->getConfig();

        // distinguiosh between libraries custom logout url
        if ($library instanceof Library) {
            $baseUrl = $library->getLogoutUrl();
        } else {
            $baseUrl = $config->Shibboleth->logout;
        }

        if (isset($baseUrl)
            && !empty($baseUrl)
        ) {
            $append = (strpos($baseUrl, '?') !== false) ? '&'
                : '?';
            $url = $baseUrl . $append . 'return='
                . urlencode($url);
        }

        // Send back the redirect URL (possibly modified):
        return $url;
    }
}
