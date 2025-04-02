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
use Laminas\Http\Client;
use Laminas\Http\Client as HttpClient;
use Laminas\Session\ManagerInterface;
use VuFind\Db\Entity\UserEntityInterface;
use VuFind\Exception\Auth as AuthException;

class Koha extends \VuFind\Auth\AbstractBase
{
    protected ManagerInterface $sessionManager;

    protected Libraries $library;
    protected $isil;

    /**
     * @param ManagerInterface $sessionManager
     * @param Libraries $libraries
     * @param $isils
     */
    public function __construct(
        ManagerInterface $sessionManager,
        Libraries $libraries,
        $isils
    ) {
        $this->sessionManager = $sessionManager;
        $this->library = $libraries->getFirstActive($isils);
        $isil = array_shift($isils);
        $this->isil = $isil;
    }

    /**
     * @param $request
     *
     * @return UserEntityInterface
     * @throws AuthException
     */
    public function authenticate($request): UserEntityInterface
    {
        if ($this->validateCredentials($request)) {
            $username =  trim($request->getPost()->get('username'));
            // If we made it this far, we should log in the user!
            //$user = $this->getUserTable()->getByUsername($username);
            $user = $this->getOrCreateUserByUsername($username);

            $user->save();
            return $user;
        }
        // if we got so far, there is obviously no user.
        throw new AuthException('authentication_error_invalid');
    }

    /**
     * Validate the credentials in the provided request, but do not change the state
     * of the current logged-in user. Return true for valid credentials, false
     * otherwise.
     *
     * @param \Laminas\Http\PhpEnvironment\Request $request Request object containing
     * account credentials.
     *
     * @throws AuthException
     * @return bool
     */
    public function validateCredentials($request)
    {
        $username = trim($request->getPost()->get('username'));
        $password = trim($request->getPost()->get('password'));

        if ($username == '' || $password == '') {
            throw new AuthException('authentication_error_blank');
        }

        $config = $this->getConfig();

        $serviceid = $config->get('Koha')->get('serviceid:'.$this->isil);
        $apikey = $config->get('Koha')->get('apikey:'.$this->isil);

        $query_url = $config->get('Koha')->get('url:'.$this->isil);
        $query_url = str_replace('%isil%', $this->isil, $query_url);

        $data = [
            'user' => $username,
            'password' => $password,
            'service' => $serviceid,
        ];
        $json = json_encode($data);

        $client = new HttpClient();
        $client->setEncType(Client::ENC_URLENCODED);
        $client->setAdapter('\Laminas\Http\Client\Adapter\Curl')
            ->setUri($query_url)
            ->setMethod('POST')
            ->setOptions(['timeout' => 30])
            ->setHeaders([
                'accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-API-Key' => $apikey
            ])
            ->setRawBody($json);
        $response = $client->send();

        $json_response = $response->getContent();
        $data_response = json_decode($json_response);

        if ($response->getStatusCode() === 200 && $data_response->auth == true) {
            return true;
        } elseif ($response->getStatusCode() === 403) {
            throw new AuthException('Invalid API token: '.$data_response->detail);
        } else {
            throw new AuthException($data_response->message);
        }
    }

    /**
     * Check if configuration is valid
     *
     * @return void
     * @throws AuthException
     */
    public function validateConfig()
    {
        $requiredKeys = ['url', 'serviceid', 'apikey'];
        if (!isset($this->config->Koha)) {
            throw new AuthException(
                "Koha section is missing in your config.ini!"
            );
        }
        foreach ($requiredKeys as $req) {
            $req .= ':'.$this->isil;
            if (!isset($this->config->Koha->$req)
                || strlen($this->config->Koha->$req) === 0
            ) {
                throw new AuthException(
                    "Koha section is missing required keys (url, serviceid, apikey)!"
                );
            }
        }
    }
}
