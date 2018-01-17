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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

namespace Bsz;

/**
 * Debugging methods
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Debug  {
    
    /**
     * Determine if client IP is an internal one. 
     * @return boolean
     */
    public static function isInternal() {
        $allowedIps = [];
        $status = false;
        $allowedIps[] = '193.197.29.*';    
        $allowedIps[] = '193.197.31.*';    
        $allowedIps[] = '10.250.6.*';
        $allowedIps[] = '10.250.5.*';        
        $allowedIps[] = '10.250.4.*';        
        $allowedIps[] = '127.0.0.1';
        $allowedIps[] = '::1';
        
        
        foreach ($allowedIps as $key => $ip) {
            // replace dots for regex use
            $allowedIps[$key] = str_replace('.', '\.', $ip);
            $allowedIps[$key] = str_replace('*', '.*', $ip);
        }
        
        $regex = '/'.implode('|', $allowedIps).'/';
        
        $clientIp = $_SERVER['REMOTE_ADDR'];
        if(isset($clientIp) && preg_match($regex, $clientIp)) {
            $status = true;
        }
        return $status;
    }
    /**
     * Prints variables (only if internal IP)
     * @param mixed $content
     */
    public static function dump($content = '') {
        if(static::isInternal()) {
            $backtrace= debug_backtrace();
            $debug = array_shift($backtrace);
            $unit=array('b','kb','mb','gb','tb','pb');
            $usage = memory_get_usage();
            $mem = round($usage/pow(1024,($i=floor(log($usage,1024)))),2).' '.$unit[$i];
            echo '<pre style="background-color: #FFF68F; padding: 5px;">';
            echo '<div style="font-size: 90%;">';
            echo '<b>File:</b> '.  $debug['file']."\n";
            echo '<b>Line:</b> '.$debug['line']."  ";
            echo '<b>Memory:</b> '.$mem."\n";
            echo '</div>';
            echo var_dump($content);             
            echo '</pre>';
        }
    }
    
    
    public static function isDev() {
         if(isset($_SERVER['HTTP_HOST'])) {
            $host = strtolower($_SERVER['HTTP_HOST']);
            if(strpos($host, 'boss2test') !== FALSE) {
                return true;
            }
            else 
            {
                return false;
            }
        }
    }

}
