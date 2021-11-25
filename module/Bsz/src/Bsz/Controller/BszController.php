<?php

/*
 * Copyright 2020 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
namespace Bsz\Controller;

use Bsz\Config\Client;
use Bsz\Config\Libraries;
use Bsz\Exception;
use VuFind\Controller\AbstractBase;
use Zend\Http\Header\SetCookie;
use Zend\Session\Container as SessionContainer;
use Zend\Session\SessionManager;

/**
 * Für statische Seiten etc.
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class BszController extends AbstractBase
{
    protected $libraries;

    /**
     * Write isil into Session
     */
    public function saveIsilAction()
    {
        if ($this->libraries === null) {
            $this->libraries = $this->serviceLocator->get('Bsz\Config\Libraries');
        }

        $isilsRoute = explode(',', $this->params()->fromRoute('isil'));
        $isilsGet = (array)$this->params()->fromQuery('isil');
        $isils = array_merge($isilsRoute, $isilsGet);
        $isils = array_filter($isils);

        if (!is_array($isils)) {
            $isils = (array)$isils;
        }

        // handle errors
        if (count($isils) == 0) {
            throw new Exception('parameter isil missing', 532);
        }

        $active = $this->libraries->getActive($isils);
        if (count($active) == 0) {
            throw new Exception('Your ISILs are not valid or inactive', 533);
        }

        if (count($isils) > 0) {
            $session = new SessionContainer(
                'fernleihe',
                $this->serviceLocator->get(SessionManager::class)
            );
            $session->offsetSet('isil', $isils);
            $uri= $this->getRequest()->getUri();
            $cookie = new SetCookie(
                'isil',
                implode(',', $isils),
                time() + 14 * 24 * 60 * 60,
                '/',
                $uri->getHost()
            );
            $header = $this->getResponse()->getHeaders();
            $header->addHeader($cookie);
        }
        $referer = $this->params()->fromQuery('referer');
        // try to get referer from param
        if (empty($referer)) {
            $referer = $this->params()->fromHeader('Referer');
        }
        if (is_object($referer)) {
            $referer = $referer->getFieldValue();
        }
        if (!empty($referer) && strpos($referer, 'saveIsil') === false
            && (strpos($referer, '.boss') > 0
                || strpos($referer, '.localhost') > 0)
        ) {
            return $this->redirect()->toUrl($referer);
        } else {
            return $this->forwardTo('search', 'home');
        }
    }

    /**
     * Show Privacy information
     */
    public function privacyAction()
    {
        // no code needed her, just do the default.
    }

    /**
     * Offers a searchbox only layout for iframe embedding
     */
    public function frameAction()
    {
        $view = $this->createViewModel();
        $view->setTerminal(true);
        return $view;
    }

    /**
     * @return \Zend\View\Model\ViewModel
     */
    public function dedupAction()
    {
        $params = [];
        $dedup = $this->serviceLocator->get('VuFindResultsGrouping\Config\Grouping');

        $post = $this->params()->fromPost();

        // store form date in session and cookie
        if (isset($post['submit_dedup_form'])) {
            $params = $dedup->store($post);
            $this->flashMessenger()->addSuccessMessage('dedup_settings_success');
        } else {
            // Load default values from session or config
            $params = $dedup->getCurrentSettings();
        }

        $view = $this->createViewModel();
        $view->setVariables($params);

        return $view;
    }

    /**
     * @return \Zend\Http\Response
     */
    public function libraryAction()
    {
        $client = $this->serviceLocator->get(Client::class);
        $libraries = $this->serviceLocator->get(Libraries::class);
        $library = $libraries->getFirstActive($client->getIsils());
        $homepage = $library->getHomepage();
        return $this->redirect()->toUrl($homepage);
    }

    /**
     * @return \Zend\View\Model\ViewModel
     */
    public function resigningAction()
    {
        $view = $this->createViewModel();

        $params = $this->params()->fromQuery();
        $params['isn'] = $params['isbn'] ?? '';
        $params['isn'] .= $params['issn'] ?? '';

        $selectedNetworks = [];
        if (isset($params['verbuende'])) {
            $selectedNetworks = explode(',', $params['verbuende']);
        }
        $allNetworks = [
            'SWB' => 'DE-576',
            'GBV' => 'DE-601',
            'KOBV' => 'DE-602',
            'HEBIS' => 'DE-603',
            'BVB' => 'DE-604',
            'HBZ' => 'DE-605',
        ];

        if (isset($params['bestellid'])) {

            $session = new SessionContainer(
                'fernleihe',
                $this->serviceLocator->get(SessionManager::class)
            );
            $session->offsetSet('bestellid', $params['bestellid']);
        }
        $view->setVariables([
            'params' => $params,
            'allNetworks' => $allNetworks,
            'selectedNetworks' => $selectedNetworks
        ]);
        return $view;
    }
}
