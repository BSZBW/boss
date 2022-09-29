<?php
/*
 * Copyright 2022 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
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
 *
 */
namespace Bsz\Net;

class Tools
{

    /**
     * Da a ping test on specified host
     *
     * @param $url
     *
     * @return bool
     */
    public static function pingDomain($url)
    {
        $urlparts = parse_url($url);
        $domain = $urlparts['host'];
        $return = [];
        $cmd = ['ping', '-c1', escapeshellarg($domain)];

        try {
            exec(implode(' ', $cmd), $return);
            return isset($return[4]) ? preg_match('/1 received, 0% packet loss/', $return[4]) : false;
        } catch (Exception $e) {
            throw new \Bsz\Exception('Unable to check zfl server status');
            return false;
        }
    }

}