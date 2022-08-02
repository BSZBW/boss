<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bsz\AjaxHandler;

use Bsz\Config\Libraries;
use Laminas\Mvc\Controller\Plugin\Params;

/**
 * AjaxHandler for the libraries typeahead
 *
 * @author amzar
 */
class LibrariesTypeahead extends \VuFind\AjaxHandler\AbstractBase
{
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

    /**
     * Returns a JSON list of libraries that match the name
     *
     * @param Params $params
     * @return HTTP Response
     */
    public function handleRequest(Params $params)
    {
        $json = [];
        $code = 500;
        $query = $params->fromQuery('q');
        $boss = $params->fromQuery('boss');
        if (!empty($query)) {
            $dbresult = $this->libraries->getActiveByName($query, 10, $boss);
            $code = 200;
            foreach ($dbresult as $library) {
                $json[] = [
                    'id' => $library->getIsil(),
                    'name' => $library->getName() . ' (' . $library->getIsil() . ')'
                ];
            }
        }
        return $this->formatResponse($json, $code);
    }
}
