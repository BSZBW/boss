<?php
namespace Bsz\Controller;

use Laminas\Http\Header\SetCookie;
use Laminas\Session\Container as SessionContainer;
use Laminas\Session\SessionManager;
use VuFind\Exception\Auth as AuthException;

/**
 * Bsz adaption of MyResearchController
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class MyResearchController extends \VuFind\Controller\MyResearchController
{
    /**
     * homeAction() taken form Production Server!!!
     */

    /**
     * Prepare and direct the home page where it needs to go
     *
     * @return mixed
     */
    public function homeAction()
    {
        // Process login request, if necessary (either because a form has been
        // submitted or because we're using an external login provider):
        if ($this->params()->fromPost('processLogin')
            || $this->getSessionInitiator()
            || $this->params()->fromPost('auth_method')
            || $this->params()->fromQuery('auth_method')
        ) {
            try {
                if (!$this->getAuthManager()->getIdentity()) {
                    $this->getAuthManager()->login($this->getRequest());
                    // Return early to avoid unnecessary processing if we are being
                    // called from login lightbox and don't have a followup action or
                    // followup is set to referrer.
                    if (
                        $this->params()->fromPost('processLogin')
                        && $this->inLightbox()
                        && (!$this->hasFollowupUrl()
                            || $this->followup()->retrieve('isReferrer') === true)
                    ) {
                        $this->clearFollowupUrl();
                        return $this->getRefreshResponse();
                    }
                }
            } catch (AuthException $e) {
                $this->processAuthenticationException($e);
            }
        }

        // Not logged in?  Force user to log in:
        if (!$this->getAuthManager()->getIdentity()) {
            //$this->setFollowupUrlToReferer();
            //return $this->forwardTo('MyResearch', 'Login');

            if (
                $this->followup()->retrieve('lightboxParent')
                && $url = $this->getAndClearFollowupUrl(true)
            ) {
                return $this->redirect()->toUrl($url);
            }

            // Allow bypassing of post-login redirect
            if ($this->params()->fromQuery('redirect', true)) {
                $this->setFollowupUrlToReferer();
            }
            return $this->forwardTo('MyResearch', 'Login');
        }
        // Logged in?  Forward user to followup action
        // or default action (if no followup provided):
        $url = $this->params()->fromQuery('followupUrl');
        if (empty($url)) {
            $url = $this->getAndClearFollowupUrl();
        }
        if (!empty($url)) {
            $this->clearFollowupUrl();
            // If a user clicks on the "Your Account" link, we want to be sure
            // they get to their account rather than being redirected to an old
            // followup URL. We'll use a redirect=0 GET flag to indicate this:
            if ($this->params()->fromQuery('redirect', true)) {
                return $this->redirect()->toUrl($url);
            }
        }

        $config = $this->getConfig();
        $page = isset($config->Site->defaultAccountPage)
            ? $config->Site->defaultAccountPage : 'Favorites';

        // Default to search history if favorites are disabled:
        if ($page == 'Favorites' && !$this->listsEnabled()) {
            return $this->forwardTo('Search', 'History');
        }
        return $this->forwardTo('MyResearch', $page);
    }


//    /**
//     * Prepare and direct the home page where it needs to go
//     *
//     * @return mixed
//     */
//    public function homeAction()
//    {
//        // Process login request, if necessary (either because a form has been
//        // submitted or because we're using an external login provider):
//        if ($this->params()->fromPost('processLogin')
//            || $this->getSessionInitiator()
//            || $this->params()->fromPost('auth_method')
//            || $this->params()->fromQuery('auth_method')
//        ) {
//            try {
//                if (!$this->getAuthManager()->isLoggedIn()) {
//                    $this->getAuthManager()->login($this->getRequest());
//                }
//            } catch (AuthException $e) {
//                $this->processAuthenticationException($e);
//            }
//        }
//
//        // Not logged in?  Force user to log in:
//        if (!$this->getAuthManager()->isLoggedIn()) {
//            $this->setFollowupUrlToReferer();
//            return $this->forwardTo('MyResearch', 'Login');
//        }
//        // Logged in?  Forward user to followup action
//        // or default action (if no followup provided):
//        $url = $this->params()->fromQuery('followupUrl');
//        if (empty($url)) {
//            $url = $this->getFollowupUrl();
//        }
//        if (!empty($url)) {
//            $this->clearFollowupUrl();
//            // If a user clicks on the "Your Account" link, we want to be sure
//            // they get to their account rather than being redirected to an old
//            // followup URL. We'll use a redirect=0 GET flag to indicate this:
//            if ($this->params()->fromQuery('redirect', true)) {
//                return $this->redirect()->toUrl($url);
//            }
//        }
//
//        $config = $this->getConfig();
//        $page = isset($config->Site->defaultAccountPage)
//            ? $config->Site->defaultAccountPage : 'Favorites';
//
//        // Default to search history if favorites are disabled:
//        if ($page == 'Favorites' && !$this->listsEnabled()) {
//            return $this->forwardTo('Search', 'History');
//        }
//        return $this->forwardTo('MyResearch', $page);
//    }

    /**
     * First destroys the selected ISIL in session and cookie, then usual logout.
     *
     * @return mixed
     */
    public function logoutAction()
    {
        $session = new SessionContainer(
            'fernleihe',
            $this->serviceLocator->get(SessionManager::class)
        );
        $session->offsetUnset('isil');
        $uri= $this->getRequest()->getUri();
        $cookie = new SetCookie(
            'isil',
            '',
            // destroy cookie by setting its life time to yesterday
            time() - 24*60*60,
            '/',
            $uri->getHost()
        );
        return parent::logoutAction();
    }
}
