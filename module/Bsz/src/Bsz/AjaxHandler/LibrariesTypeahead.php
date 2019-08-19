<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Bsz\AjaxHandler;

use Zend\Mvc\Controller\Plugin\Params,
    VuFind\Resolver\Driver\PluginManager as ResolverManager,
    VuFind\Session\Settings as SessionSettings,
    Zend\Config\Config,
    Zend\View\Renderer\RendererInterface,
    Bsz\Config\Libraries;

/**
 * AjaxHandler for the libraries typeahead
 *
 * @author amzar
 */
class LibrariesTypeahead extends \VuFind\AjaxHandler\AbstractBase {
    
    
    /**
     *
     * @var Bsz\Config\Libraries
     */
    protected $libraries;
    /**
     * Constructor
     *
     * @param Bsz\Config\Libraries  $libraries
     */
    public function __construct(Libraries $libraries
    ) {
        $this->libraries = $libraries;
    }
    
    public function handleRequest(Params $params) {
        
        if ($params->fromQuery('q') !== '') {
            $query = $params->fromQuery('q');
//            $dbresult = $this->libraries->getActiveByName($query);
//            foreach($dbresult as $row) {
//                
        }
        $arrReturn = [
            [
                'id' => 1,
                'name' => 'Winkler, Stefan',
            ],
            [
                'id' => 1,
                'name' => 'Kleiber, Ulrich',
            ],
            [
                 'id' => 2,
                'name' => 'Amzar, Cornelius',
            ]
        ];
        return $this->formatResponse($arrReturn, 200);  
    }
}
