<?php

namespace BszCommon\Controller\Plugin;

class UrlDecorator extends \Zend\Mvc\Controller\Plugin\Url
{
    private $url;

    public function __construct(\Zend\Mvc\Controller\Plugin\Url $url)
    {
        $this->url = $url;
        $this->setController($this->url->controller);
    }

    public function fromRoute($route = null, $params = [], $options = [], $reuseMatchedParams = false)
    {
        $regex = '/([Ss]earch)([3-9][0-9]*|[1-9][0-9]+)([a-zA-Z][a-zA-z0-9]*|)(-[a-zA-z0-9]+|)/';
        $matches = [];
        if (preg_match($regex, $route, $matches)) {
            $routeName = 'searchn' . strtolower($matches[3]) . $matches[4];
            $params = array_merge($params, ['controller' => ucfirst($matches[1]) . ucfirst($matches[2])]);
            return $this->url->fromRoute($routeName, $params, $options, $reuseMatchedParams);
        }
        return $this->url->fromRoute($route, $params, $options, $reuseMatchedParams);
    }

    public function __call($method, $params)
    {
        if($method == 'fromRoute'){
            return call_user_func_array(array($this, $method), $params);
        }
        return call_user_func_array(array($this->url, $method), $params);
    }

}