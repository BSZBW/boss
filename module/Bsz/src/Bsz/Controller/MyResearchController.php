<?php
namespace Bsz\Controller;

use Laminas\Http\Header\SetCookie;
use Laminas\Session\Container as SessionContainer;
use Laminas\Session\SessionManager;

/**
 * Bsz adaption of MyResearchController
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class MyResearchController extends \VuFind\Controller\MyResearchController
{

    public function homeAction()
    {
        $result = parent::homeAction();
        $url = $this->params()->fromQuery('followupUrl');
        if (!empty($url)) {
            return $this->redirect()->toUrl($url);
        }
        return $result;
    }

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
