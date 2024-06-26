<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bsz\AjaxHandler;

use Bsz\Config\Libraries;
use Laminas\Http\Header\SetCookie;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\Session\Container as SessionContainer;

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
     * @var \Laminas\Session\Container
     */
    protected $sessionContainer;

    /**
     *
     * @var \Laminas\Http\Response;
     */
    protected $response;

    protected $host;

    public function __construct(Libraries $libraries,
        SessionContainer $container, Response $response, $host
    ) {
        $this->libraries = $libraries;
        $this->sessionContainer = $container;
        $this->response = $response;
        $this->host = $host;
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
            $this->sessionContainer->offsetSet('isil', $isils);
            $secure = getenv('VUFIND_ENV') === 'production' ? true : false;
            $cookie = new SetCookie(
                    'isil',
                    implode(',', $isils),
                    time() + 14 * 24 * 60 * 60,
                    '/',
                    $this->host,
                    $secure);
            $header = $this->response->getHeaders();
            $header->addHeader($cookie);
        }
        return $this->formatResponse($isils, $code);
    }
}
