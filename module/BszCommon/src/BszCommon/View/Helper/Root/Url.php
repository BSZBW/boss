<?php

namespace BszCommon\View\Helper\Root;

class Url extends \VuFind\View\Helper\Root\Url
{
    public function __invoke($name = null, $params = [], $options = [], $reuseMatchedParams = false)
    {
        $regex = '/([Ss]earch)([3-9][0-9]*|[1-9][0-9]+)([a-zA-Z][a-zA-z0-9]*|)(-[a-zA-z0-9]+|)/';
        $matches = [];
        if (preg_match($regex, $name, $matches)) {
            $routeName = 'searchn' . strtolower($matches[3]) . $matches[4];
            $params = array_merge($params, ['controller' => ucfirst($matches[1]) . ucfirst($matches[2]) . ucfirst($matches[3])]);
            return parent::__invoke($routeName, $params, $options, $reuseMatchedParams);
        }
//        foreach (['/', '-', ''] as $delim) {
//            $matches = [];
//            $regex = '/([Ss]earch[3-9][0-9]*)(' . preg_quote($delim, '/') . '(.+)?)/';
//            if(preg_match($regex, $name, $matches)) {
//                $routeName = 'searchn' . (empty($matches[2]) ? '' : $matches[2]);
//                $params = array_merge($params, ['controller' => $name]);
//                return parent::__invoke($routeName, $params, $options, $reuseMatchedParams);
//            }
//        }
        return parent::__invoke($name, $params, $options, $reuseMatchedParams);
    }
}