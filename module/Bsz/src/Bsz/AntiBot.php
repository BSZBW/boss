<?php
/*
 * Copyright 2021 (C) Bibliotheksservice-Zentrum Baden-
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

namespace Bsz;

use Zend\Config\Config;

class AntiBot
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function getForms()
    {
        return explode(',', $this->config->forms);
    }

    public function generateTimeHash($key)
    {
        $time = new \DateTime();
        $cipher = 'aes-128-cbc';

        if (in_array($cipher, openssl_get_cipher_methods())) {
            return openssl_encrypt($time->getTimestamp(), $cipher, $key, 0, 'changemeeeeeeeee');
        }
        throw new Exception('Cipher method not found');
    }
}
