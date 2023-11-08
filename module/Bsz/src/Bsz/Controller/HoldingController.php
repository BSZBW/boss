<?php

/*
 * Copyright 2020 (C) Bibliotheksservice-Zentrum Baden-
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
namespace Bsz\Controller;

use VuFind\Controller\AbstractBase;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Json\Json;

/**
 * Holding Actions
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class HoldingController extends AbstractBase
{
    public function queryAction()
    {
        $isxns =    (array)$this->params()->fromQuery('isxn');
        $network = $this->checkNetwork($this->params()->fromQuery('network'));
        $year = $this->params()->fromQuery('year');
        $zdb = $this->params()->fromQuery('zdb');
        $title = $this->params()->fromQuery('title');
        $author = $this->params()->fromQuery('author');

        $this->holding = $this->serviceLocator->get('Bsz\ILL\Holding');
        $response = $this->getResponse();
        $response instanceof Response;
        $response->getHeaders()->addHeaderLine('content-type', 'application/json');

        $this->holding->setIsxns($isxns)
                ->setNetwork($network)
                ->setYear($year)
                ->setTitle($title)
                ->setAuthor($author)
                ->setZdbId($zdb);

        if ($this->holding->checkQuery() && $network !== false) {
            $result = $this->holding->query();
            $response->setContent(Json::encode($result));
        } else {
            $result = [
                'error' => 'isxn[] (or title and author ) and network params are'
                . ' mandatory! Network must be a valid German library network '
                . 'shortcut. zdb can hold the ZDB-ID. ',
                'numfound' => 0,
            ];
            $response->setContent(Json::encode($result));
        }
        return $response;
    }

    /**
     * Validate network string
     *
     * @param string $network
     *
     * @return boolean|string
     */
    protected function checkNetwork($network)
    {
        $client = $this->serviceLocator->get('Bsz\Config\Client');
        $networks = $client->getNetworks();
        if (in_array($network, $networks)) {
            return array_flip($networks)[$network];
        }
        return false;
    }
}
