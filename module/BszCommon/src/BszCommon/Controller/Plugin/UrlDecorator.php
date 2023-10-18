<?php

/*
 * Copyright (C) 2023 Bibliotheks-Service Zentrum, Konstanz, Germany
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

namespace BszCommon\Controller\Plugin;

use BszCommon\SearchRouteRewriteTrait;

class UrlDecorator extends \Zend\Mvc\Controller\Plugin\Url
{

    use SearchRouteRewriteTrait;

    private $url;

    public function __construct(\Zend\Mvc\Controller\Plugin\Url $url)
    {
        $this->url = $url;
    }

    public function fromRoute(
        $route = null,
        $params = [],
        $options = [],
        $reuseMatchedParams = false
    ) {
        $rewrite = $this->rewrite($route);
        return $this->url->fromRoute(
            $rewrite['route'],
            array_merge($params, $rewrite['params']),
            $options,
            $reuseMatchedParams
        );
    }

    public function __call($method, $params)
    {
        if ($method == 'fromRoute') {
            return call_user_func_array(array($this, $method), $params);
        }
        return call_user_func_array(array($this->url, $method), $params);
    }

}
