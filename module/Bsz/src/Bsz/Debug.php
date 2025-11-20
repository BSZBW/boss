<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 */
namespace Bsz;

/**
 * Debugging methods
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Debug
{
    /**
     * Determine if client IP is an internal one.
     * @return boolean
     */
    public static function isInternal()
    {
        $status = false;

        $allowedIps = [];
        // VPN
        $allowedIps[] = '192.168.5.*';

        $allowedIps[] = '193.197.29.*';
        $allowedIps[] = '193.197.31.*';
        $allowedIps[] = '10.250.6.*';
        $allowedIps[] = '10.250.5.*';
        $allowedIps[] = '10.250.4.*';
        $allowedIps[] = '127.0.0.1';
        // Uwe Reh
        $allowedIps[] = '141.2.164.*';
        $allowedIps[] = '141.2.165.*';
        // Uwe Reh Homeoffice
//        $allowedIps[] = gethostbyname('efflebach.selfhost.eu');
        // Stefan Lohrum
        $allowedIps[] = '130.73.63.*';

        $allowedIps[] = '::1';

        foreach ($allowedIps as $key => $ip) {
            // replace dots for regex use
            $allowedIps[$key] = str_replace('.', '\.', $ip);
            $allowedIps[$key] = str_replace('*', '.*', $ip);
        }

        $regex = '/' . implode('|', $allowedIps) . '/';

        $clientIp = $_SERVER['REMOTE_ADDR'];
        if (isset($clientIp) && preg_match($regex, $clientIp)) {
            $status = true;
        }
        return $status;
    }

    /**
     * Determin if running on Test Server
     * @return boolean
     */
    public static function isDev()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = strtolower($_SERVER['HTTP_HOST']);
            if (strpos($host, 'boss2test') !== false ||
                   strpos($host, 'bosstest') !== false) {
                return true;
            }
        }
        return false;
    }
}
