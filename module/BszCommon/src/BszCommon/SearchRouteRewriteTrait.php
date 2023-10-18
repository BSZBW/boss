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

namespace BszCommon;

trait SearchRouteRewriteTrait
{
    public function rewrite(string $route): array
    {
        $retVal = [];

        $regex
            = '/search([3-9][0-9]*|[1-9][0-9]+)([a-z][a-z0-9]*|)(-[a-z0-9]+|)/';
        $matches = [];
        if (preg_match($regex, strtolower($route), $matches)) {
            $retVal['route'] = 'searchn' . strtolower($matches[2])
                . $matches[3];
            $retVal['params'] = [
                'controller' => 'Search' . ucfirst($matches[1])
                    . ucfirst($matches[2])
            ];
        } else {
            $retVal['route'] = $route;
            $retVal['params'] = [];
        }
        return $retVal;
    }
}
