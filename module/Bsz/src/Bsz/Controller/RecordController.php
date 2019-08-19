<?php
/**
 * Record Controller
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Controller
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Bsz\Controller;
use VuFind\RecordDriver\AbstractBase as AbstractRecordDriver;
use Zend\View\Model\ViewModel;
use Zend\Log\LoggerAwareInterface as LoggerAwareInterface;
use Zend\Config\Config as Config;
use Zend\ServiceManager\ServiceManager as ServiceManager;


/**
 * This class was created to make a default record tab behavior possible
 */
class RecordController extends \VuFind\Controller\RecordController 
    implements LoggerAwareInterface 
{
    use IsilTrait;
    use \VuFind\Controller\HoldsTrait;
    use \VuFind\Controller\ILLRequestsTrait;
    use \VuFind\Controller\StorageRetrievalRequestsTrait;
    use \VuFind\Log\LoggerAwareTrait;
    
    const TIMEOUT = 120;
    
    protected $orderId = 0;
    
        /**
     * Constructor
     *
     * @param ServiceLocatorInterface $sm     Service manager
     * @param Config                  $config VuFind configuration
     */
    public function __construct(ServiceManager $sm, Config $config)
    {
        parent::__construct($sm, $config);
        // Don't know how this can be dont using Traits, eg. at the DAIA class. 
        $this->logger = $sm->get(\VuFind\Log\Logger::class);
    }
    
     /**
     * Default tab for Solr is holdings, excepts its a collection, then volumes. 
     *
     * @param AbstractRecordDriver $driver Record driver
     *
     * @return string
     */
    protected function getDefaultTabForRecord(AbstractRecordDriver $driver)
    {
        // Load configuration:
        $config = $this->getTabConfiguration();

        // Get the current record driver's class name, then start a loop
        // in case we need to use a parent class' name to find the appropriate
        // setting.
        $className = get_class($driver);
        while (true) {
            $multipart = $driver->tryMethod('getMultipartLevel');
            if(isset($multipart)) {
                if($multipart == \Bsz\RecordDriver\SolrMarc::MULTIPART_COLLECTION) {
                    return 'Volumes';
                }
                else {
                    return 'Holdings';
                }
            }
            elseif (isset($config[$className]['defaultTab'])) {
                return $config[$className]['defaultTab'];
            }
            $className = get_parent_class($className);
            if (empty($className)) {
                // No setting found...
                return null;
            }
        }
    }
    
     /**
     * Render ILL form, check password and submit
     */
    public function ILLFormAction()
    {
        $isils = $this->params()->fromQuery('isil');
        if ($isils) {
            return $this->processIsil();
        }
        $params = $this->params()->fromPost();
        $config = $this->serviceLocator->get('Bsz\Config\Client')->get('ILL');
        // If Request does not have this param, we should not use collapsible 
        // panels
        $success = null;
        $route = $this->params()->fromRoute();
        
        $this->driver = isset($route['id']) ? $this->loadRecord() : null;
        $this->baseUrl = $this->isTestMode() ? $config->get('baseurl_test') :
                $config->get('baseurl_live');
        $this->baseUrlAuth = $this->isTestMode() ? $config->get('baseurl_auth_test') :
                $config->get('baseurl_auth_live');
     
        $authManager = $this->serviceLocator->get('VuFind\AuthManager');
        $client = $this->serviceLocator->get('Bsz\Config\Client');
        if ($client->isIsilSession() && !$client->hasIsilSession()) {
            $this->flashMessenger()->addErrorMessage('missing_isil');
            throw new \Bsz\Exception('You must select a library to continue');
        } 
        $libraries = $this->serviceLocator->get('Bsz\Config\Libraries');
        $first = $libraries->getFirstActive($client->getIsils());
        $submitDisabled = false;
        
        if (isset($first) && $authManager->loginEnabled() 
                && !$authManager->isLoggedIn()
                && $first->getAuth() == 'shibboleth') {
            $this->flashMessenger()->addErrorMessage('You must be logged in first');
            $submitDisabled = true;
        }

        // validate form data
        if (isset($params['Bestellform'])) {

            // use regex to trim username
            if (isset($first) && strlen($first->getRegex()) > 0 && $first->getAuth() == 'shibboleth') {
                $params['BenutzerNummer'] = preg_replace($first->getRegex(), "$1", $params['BenutzerNummer']);         
            }
            // response is  okay
            if ($this->checkAuth($params)) {
                // remove password from TAN field
                unset($params['Passwort']);
                
                // free form uses a Jahr field which must be copies into Jahrgang und EJahr
                if (isset($params['Jahr'])) {
                    $params['EJahr'] =  $params['Jahr'];
                    $params['Jahrgang'] = $params['Jahr'];                    
                }
                
                $response = $this->doRequest($this->baseUrl . "/flcgi/pflauftrag.pl", $params);               
                
                try {                    
                    $dom = new \Zend\Dom\Query($response->getBody());
                    $message = $dom->queryXPath('ergebnis/text()')->getDocument();
                    $success = $this->parseResponse($message);    

                } catch (\Exception $ex) {
                    $this->flashMessenger()->addErrorMessage('request_error_technical');
                    $this->logError($params['Sigel'].': Error while parsing HTML response from ZFL server');
                }
            } else { // wrong credentials
                $this->flashMessenger()->addErrorMessage('request_error_blocked');
                $this->logError($params['Sigel'].': ILL request blocked. Checkauth failed');
                $success = false;
            }
        }
        $uri= $this->getRequest()->getUri();
        $cookie = new \Zend\Http\Header\SetCookie(
            'orderStatus', 
            $success ? 1 : 0, 
            time()+ 60 * 60 * 2, 
            '/',
            $uri->getHost() );
            $header = $this->getResponse()->getHeaders();
            $header->addHeader($cookie);
        $view = $this->createViewModel([
                    'driver' => $this->driver,
                    'success' => $success,
                    'test' => $this->isTestMode(),
                    'params' => $params,
                    'submitDisabled' => $submitDisabled,
                    'orderId' => $this->orderId
                ])->setTemplate('record/illform');
        return $view;
    }
    
    public function freeFormAction() {
        // if one accesses this form with a library that uses custom form, 
        // redirect. 
        $client = $this->serviceLocator->get('Bsz\Config\Client');
                $authManager = $this->serviceLocator->get('VuFind\AuthManager');
        $isils = $this->params()->fromQuery('isil');
        
        if ($isils) {
            return $this->processIsil();
        }
        
        if ($client->isIsilSession() && !$client->hasIsilSession() && count($isils) == 0) {
            $this->flashMessenger()->addErrorMessage('missing_isil');
            throw new \Bsz\Exception('You must select a library to continue');
        }
        $libraries = $this->serviceLocator->get('Bsz\Config\Libraries');
        $first = $libraries->getFirstActive($client->getIsils());
        if ($first !== null && $first->hasCustomUrl()) {
            return $this->redirect()->toUrl($first->getCustomUrl());
        }
        $submitDisabled = false;
        if ($first !== null && $authManager->loginEnabled() 
                && !$authManager->isLoggedIn()
                && $first->getAuth() == 'shibboleth') {
            $this->flashMessenger()->addErrorMessage('You must be logged in first');
            $submitDisabled = true;
        }      
        
                
        $view = $this->createViewModelWithoutRecord([
            'success' => null,
            'driver' => null,
            'test' => $this->isTestMode(),
            'submitDisabled' => $submitDisabled
        ]);
        $view->setTemplate('record/illform.phtml');
        return $view;
    }

    /**
     * Determin if we should use the test or live url. 
     * @return boolean
     */
    public function isTestMode()
    {
        $client = $this->serviceLocator->get('Bsz\Config\Client');
        $libraries = $this->serviceLocator->get('Bsz\Config\Libraries')
                ->getActive($client->getIsils());
        $test = true;
        foreach ($libraries as $library) {
            if ($library->isLive()) {
                $test = false;
            }
        }
        return $test;
    }

    /**
     * Determin if we should use the test or live url. 
     * @return boolean
     */
    public function getCustomUrl()
    {
        $client = $this->serviceLocator->get('Bsz\Config\Client');
        $libraries = $this->serviceLocator->get('Bsz\Config\Libraries')
                ->getActive($client->getIsils());

        foreach ($libraries as $library) {
            if ($library->hasCustomUrl()) {
                return $library->getCustomUrl();
            }
        }
        return '';
    }

    /**
     * 
     * @param string $sigel
     * @return \Bsz\Config\Library
     */
    public function getLibraryBySigel($sigel)
    {
        $client = $this->serviceLocator->get('Bsz\Config\Client');
        $libraries = $this->serviceLocator->get('Bsz\Config\Libraries')
                ->getActive($client->getIsils());

        foreach ($libraries as $library) {
            if ($library->getSigel() == $sigel) {
                return $library;
            }
        }
        return null;
    }

    /**
     * Check credentials
     * 
     * @param array $params
     * 
     * @return bool
     */
    public function checkAuth($params)
    {
        $library = $this->getLibraryBySigel($params['Sigel']);
        $config = $this->serviceLocator->get('Bsz\Config\Client')->get('ILL');     
        $status = false;

        if (isset($library)) {
            // is shibboleth auth is used, we do not need to check anything. 
            $authManager = $this->serviceLocator->get('VuFind\AuthManager');
            if ($authManager->loginEnabled() && $authManager->isLoggedIn()) {
                return true;
            }
            $authParams = [
                'sigel' => $params['Sigel'],
                'auth_typ' => $library->getAuth(),
                'user' => $params['BenutzerNummer'],
                'passwort' => $library->getAuth() == 'tan' ?
                        $params['TAN'] : $params['Passwort'],
            ];
            
            $response = $this->doRequest($this->baseUrlAuth . '/flcgi/endnutzer_auth.pl', $authParams);   
            
            try {
                $xml = simplexml_load_string($response->getBody());                

            } catch (\Exception $ex) {
                $this->logError($params['Sigel'].': Error while parsing XML'.$ex->getMessage());
                $this->flashMessenger()->addErrorMessage('request_error_technical');
            }
            $status = (isset($xml->status) && $xml->status == 'FLOK');            
        } else {
            $this->flashMessenger()->addErrorMessage('request_error_blocked');
            $this->logError('ILL request blocked. Sigel not found ');
            $status = false;
        }
        return $status;
    }

    /**
     * Parse HTML response from server and output message
     * 
     * @param $html
     * 
     * @return boolean
     */
    public function parseResponse($html)
    {

        // should return 0 if no match and false if an error occurs
        // so if it matches is returns 1 which is casted to true
        if ((bool)preg_match('/Bestell-Id:\s*(\d*)/', $html->textContent, $id) === true ) {
            $this->orderId = $id[1];
            // Order is successfull
            $this->flashMessenger()->addSuccessMessage('request_submit_ok');
            return true;
        } else {
            // order not successfull - disable error reporting because 
            // preg_match errors may occur.
            $error_reporting = error_reporting();
            error_reporting(0);
            $matches = [];
            preg_match_all('/(Fehler \([a-zA-z]*\): )(.*)/s', $html->textContent, $matches);
            $lastmatch = end($matches);
            $msgTextMultiline = array_shift($lastmatch);
            $msgText = str_replace("\n", ', ', $msgTextMultiline);
            $msgText = strip_tags($msgText);
            if (mb_strlen($msgText) > 500) {
                $msgText = mb_substr($msgText, 0, 500);
            }

            if (empty($msgText)) {                
                $this->debug('HTML response from ZFL server: '.$html);   
                $this->logError('ILL error: could not parse error message out of HTML: '.$html);                
            }

            if (!empty($msgText)) {
                $this->flashMessenger()->addInfoMessage($msgText);    
                $this->logError('ILL error: message from ZFL: '.$msgText);
            }
            error_reporting($error_reporting);
            return false;
        }
    }
    /**
     * Abstract method implementations
     */
    public function getBreadcrumb()
    {
        return parent::getBreadcrumb();
    }

    public function getUniqueID()
    {
        return parent::getUniqueID();
    }
    
    
    public function createViewModelWithoutRecord($params = null) 
    {
        $layout = $this->params()
            ->fromPost('layout', $this->params()->fromQuery('layout', false));
        if ('lightbox' === $layout) {
            $this->layout()->setTemplate('layout/lightbox');
        }
        $view = new ViewModel($params);
        $this->layout()->searchClassId = $view->searchClassId = $this->searchClassId;
        // we don't use a driver in this action
        // $view->driver = $this->loadRecord();
        return $view;
    }
    
        /**
     * 
     * @return View
     */
    public function homeAction()
    {
        $isilsParam = $this->params()->fromQuery('isil');
        if ($isilsParam) {
            return $this->processIsil();
        }
        $view = parent::homeAction();
        // set OpenUrl for custom ill forms
        $view->customUrl = strlen($this->getCustomUrl()) > 0 ? $this->getcustomUrl() : false;

        $view->authMethod = '';
        $client = $this->serviceLocator->get('Bsz\Config\Client');
        $isils = $client->getIsils();
        if ($client->isIsilSession() && !$client->hasIsilSession()) {
            $this->flashMessenger()->addErrorMessage('missing_isil');
        } else if (count($isils) > 0) {
            $isil = array_shift($isils);
            $library = $this->serviceLocator->get('Bsz\Config\Libraries')->getByIsil($isil);
            $view->authMethod = $library->getAuth();
        } 
  
        return $view;
    }
    /**
     * We override this method to get rid of the driver dependency (for the free form)
     *
     * @param array $params Parameters to pass to ViewModel constructor.
     *
     * @return \Zend\View\Model\ViewModel
     */
    protected function createViewModel($params = null)
    {
        $layout = $this->params()
            ->fromPost('layout', $this->params()->fromQuery('layout', false));
        if ('lightbox' === $layout) {
            $this->layout()->setTemplate('layout/lightbox');
        }
        $view = new ViewModel($params);
        $this->layout()->searchClassId = $view->searchClassId = $this->searchClassId;
        $route = $this->params()->fromRoute();      
        $view->driver = isset($route['id']) ? $this->loadRecord() : null;
        return $view;
    }
    
    private function doRequest($url, $params) 
    {
        $config = $this->serviceLocator->get('Bsz\Config\Client')->get('ILL');
         // send real order
        $client = new \Zend\Http\Client();
        $client->setEncType(\Zend\Http\Client::ENC_URLENCODED);
        $client->setAdapter('\Zend\Http\Client\Adapter\Curl')
                ->setUri($url)
                ->setMethod('POST')
                ->setOptions(['timeout' => static::TIMEOUT])
                ->setParameterPost($params)
                ->setAuth($config->get('basic_auth_user'), str_rot13($config->get('basic_auth_pw')));
        $response = $client->send();
        
        // create GET request for better logging - this request is never sent! 
        $client->setAdapter('\Zend\Http\Client\Adapter\Curl')
                ->setUri($url)
                ->setMethod('GET')
                ->setParameterGet($params)
                ->setAuth($config->get('basic_auth_user'), str_rot13($config->get('basic_auth_pw')));
        $this->debug('ZFL query string:');
        $debug[] = $client->getRequest()->getUriString();
        $debug[] = $client->getRequest()->getQuery()->toString();
        $this->debug(implode('?', $debug));
        
        return $response;
    }
    

}
