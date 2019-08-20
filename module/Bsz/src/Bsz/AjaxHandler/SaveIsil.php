<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Bsz\AjaxHandler;

use \Zend\Mvc\Controller\Plugin\Params,
    Bsz\Config\Libraries,
    \Zend\Session\Container,
    \Zend\Http\Response,
    \Zend\Http\Header\Setcookie;


/**
 * SaveIsil stores ISILs from AJAXRequests in the session
 *
 * @author amzar
 */
class SaveIsil extends \VuFind\AjaxHandler\AbstractBase 
{
    /**
     *
     * @var Bsz\Config\Libraries
     */
    protected $libraries;
    /**
     *
     * @var \Zend\Session\Container
     */
    protected $sessionContainer;
    
    /**
     *
     * @var \Zend\Http\Response;
     */
    protected $response;
    
    
    public function __construct(Libraries $libraries, Response $response ) 
    {
        $this->libraries = $libraries;
        $this->response = $response;
        
    }
    
    public function handleRequest(Params $params): array 
    {
        $isils = (array)$params->fromQuery('isil');
        $code = 200;
        foreach ($isils as $key => $isil) {
            if (strlen($isil) < 1) {
                unset($isils[$key]);
            } 
        }
        if (count($isils) == 0) {
            $code = 500;
        }
        if (count($isils) > 0) {
            $container = new Container('fernleihe');
            $container->offsetSet('isil', $isils);     
            $uri= $this->getRequest()->getUri();
            $cookie = new Setcookie(
                    'isil', 
                    implode(',', $isils), 
                    time() + 14 * 24* 60 * 60, 
                    '/',
                    $uri->getHost() );
            $header = $this->response->getHeaders();
            $header->addHeader($cookie);
        } 
        return $this->formatResponse([], $code);
    }
}
