<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
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
 */

namespace Bsz\Controller;
use Zend\Session\Container as SessionContainer;
/**
 * Für statische Seiten etc. 
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class BszController extends \VuFind\Controller\AbstractBase {
    
    
    /**
     * Write isil into Session 
     */
    public function saveIsilAction() {        
      
        $isilsRoute = explode(',', $this->params()->fromRoute('isil'));       
        $isilsGet = (array)$this->params()->fromQuery('isil');
        $isils = array_merge($isilsRoute, $isilsGet);

        if(!is_array($isils)) {
            $isils = (array)$isils;
        }
        foreach ($isils as $key => $isil) {
            if (strlen($isil) < 1) {
                unset($isils[$key]);
            } 
        }
        if (count($isils) == 0) {
            throw new \Bsz\Exception('parameter isil missing');
        }
        if (count($isils) > 0) {
            $session = new SessionContainer(
                'fernleihe',
                $this->serviceLocator->get(\Zend\Session\SessionManager::class)
            );
            $session->offsetSet('isil', $isils);     
            $uri= $this->getRequest()->getUri();
            $cookie = new \Zend\Http\Header\SetCookie(
                    'isil', 
                    implode(',', $isils), 
                    time() + 14 * 24* 60 * 60, 
                    '/',
                    $uri->getHost() );
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
        if (!empty($referer) && strpos($referer, 'saveIsil') === FALSE
                && ( strpos($referer, '.boss') > 0 
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
    public function privacyAction() {
        // no code needed her, just do the default.
    }
    
    /**
     * Offers a searchbox only layout for iframe embedding
     */
    public function frameAction() {
        
       $view = $this->createViewModel();
       $view->setTerminal(true);
       return $view;
    }   
    
    public function dedupAction() {
        
        $params = [];
        $dedup = $this->serviceLocator->get('Bsz\Config\Dedup');
       
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
   
}

