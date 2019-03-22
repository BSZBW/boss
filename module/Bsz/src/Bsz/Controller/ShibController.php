<?php

/*
 * The MIT License
 *
 * Copyright 2017 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Bsz\Controller;

/**
 * Shibboleth Actions
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class ShibController extends \VuFind\Controller\AbstractBase
{
    
    public function __construct() {
        // we need to override abstractBase __constructor as it requires 
        // some kind of service locator crap... 
    }    
    /**
     * This action let's you choose IdP
     */
    public function wayfAction() {
        // Store the referer, so the user can return to this site after login
        $this->setFollowupUrlToReferer();
        
        $libraries = $this->getServiceLocator()->get('Bsz\Config\Libraries');
        $client = $this->getServiceLocator()->get('Bsz\Config\Client');
        $isils = $client->getIsils();
        $library = $libraries->getByIsil($client->getIsils());
        
        if ($library->getAuth() === 'shibboleth') {
            return $this->redirect()->toRoute(
                    'shib-redirect',
                    [   
                        'controller' => 'Shib',
                        'action' => 'Redirect',
                    ],
                    [
                        'query' => [
                            'isil' => array_shift($isils)                        
                        ]
                    ]
            );
        }
        
        
        
    }
    
    /**
     * this action redirects to IdP
     */
    public function redirectAction() {

        // Build target url
        $action = $this->url()->fromRoute('myresearch-home');
        $uri = $this->getRequest()->getUri();
        $baseUrl = sprintf('%s://%s', $uri->getScheme(), $uri->getHost());

        $target = $baseUrl.$action.'?auth_method=Shibboleth';
        $config = $this->getServiceLocator()->get('VuFind\Config')
                ->get('config')->get('Shibboleth');
        // build actual url
        try {
            $isil = $this->params()->fromQuery('isil');
            $libraries = $this->getServiceLocator()->get('bsz\libraries');
            $library = $libraries->getByIsil($isil);
            $idp = $library->getIdp();

            $params = [
                'SAMLDS'    => 1,
                'target'    => $target,
                'entityID'  => $idp,
            ];      
            if ((int)$config->get('forceAuthn') === 1) {
                $params['forceAuthn'] = 'true';
            }
            $url = $baseUrl.'/Shibboleth.sso/Login?'.http_build_query($params);    
            return $this->redirect()->toUrl($url);
            
        } catch (\Exception $ex) {
            throw new \Bsz\Exception('Could not redirect');
        }
        

        
    }
}
