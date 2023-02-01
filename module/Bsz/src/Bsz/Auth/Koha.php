<?php
/*
 * Copyright 2023 (C) Bibliotheksservice-Zentrum Baden-
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

namespace Bsz\Auth;

use Bsz\Config\Libraries;
use VuFind\Exception\Auth as AuthException;
use Zend\Http\Client;
use \Zend\Http\Client as HttpClient;

class Koha extends \VuFind\Auth\AbstractBase
{
    protected $libraries;
    protected $isil;
    /**
     * Constructor
     *
     * @param \Zend\Session\ManagerInterface $sessionManager Session manager
     */
    public function __construct(
        \Zend\Session\ManagerInterface $sessionManager,
        Libraries $libraries,
        $isils
    ) {
        $this->sessionManager = $sessionManager;
        $this->libraries = $libraries;
        $isil = array_shift($isils);
        $this->isil = $isil;
    }
    /**
     * @inheritDoc
     */
    public function authenticate($request)
    {
        $username = trim($request->getPost()->get('username'));
        $password = trim($request->getPost()->get('password'));
        if ($username == '' || $password == '') {
            throw new AuthException('authentication_error_blank');
        }
        return $this->checkKoha($username, $password);
    }

    protected function checkKoha($username, $password)
    {
        $config = $this->getConfig();

        $serviceid = $config->get('Koha')->get('serviceid');
        $apikey = $config->get('Koha')->get('apikey');

        $schema = $config->get('Koha')->get('schema');
        $host = $config->get('Koha')->get('host');
        $port = $config->get('Koha')->get('port');
        $path = $config->get('Koha')->get('path');
        $path = str_replace('%isil%', $this->isil, $path);

        $query_url = $schema.'://'.$host.':'.$port.$path;

        $data = [
            'user' => $username,
            'password' => $password,
            'service' => $serviceid,
        ];
        $json = json_encode($data);

        $client = new HttpClient();
        $client->setEncType(Client::ENC_URLENCODED);
        $client->setAdapter('\Zend\Http\Client\Adapter\Curl')
            ->setUri($query_url)
            ->setMethod('POST')
            ->setOptions(['timeout' => 30])
            ->setHeaders([
                'X-API-Key' => $apikey
            ])
            ->setRawBody($json);
        $response = $client->send();

        return true;
    }
}
