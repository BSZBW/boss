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
namespace Bsz\Controller;

use Bsz\Debug;
use Laminas\Http\Client;

/**
 * Hier kann man alles testen
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class TestController extends \VuFind\Controller\AbstractBase
{
    /**
     * Action to simply test some methods
     */
    public function recordAction()
    {
        $runner = $this->serviceLocator->get('VuFind\SearchRunner');
        $ppn = $this->params()->fromQuery('ppn');
        if (!empty($ppn)) {
            $params['lookfor'] = 'id:' . str_replace(['(', ')'], ['\(', '\)'], $ppn);
            $results = $runner->run($params, 'Solr');

            // now, we can do something with our record
            foreach ($results->getResults() as $record) {
                $record instanceof \Bsz\RecordDriver\SolrMarc;
                $authors = $record->getDeduplicatedAuthors(['role', 'gnd', 'live']);
                var_dump($authors['primary']);
                var_dump($authors['secondary']);
                var_dump($authors['corporate']);
            }
        } else {
            echo 'Param PPN is mandatory';
        }

        return $this->getResponse();
    }

    public function phpinfoAction()
    {
        if (Debug::isInternal()) {
            phpinfo();
        }
        return $this->getResponse();
    }

    public function zflAction()
    {
        $date = new \DateTime();
        $date->add(new \DateInterval('P100D'));

        $params = [
            'Verfasser' =>  '',
            'Titel' =>  'BSZ-Testtitel',
            'Untertitel' =>  '',
            'Auflage' =>  '',
            'Verlag' =>  'Springer-Verlag <Berlin; Heidelberg>',
            'EOrt' =>  'Konstanz',
            'EJahr' =>  '2015',
            'BandTitel' =>  '',
            'Isbn' =>  '',
            'AufsatzAutor' =>  '',
            'AufsatzTitel' =>  '',
            'Seitenangabe' =>  '',
            'Bestellform' =>  'Leihen',
            'Sigel' =>  'Mar 1',
            'ErledFrist' => $date->format('Y-m-d'),
            'AndereAuflage' =>  'on',
            'MaxKostenKopie' =>  '8',
            'Bemerkung' =>  '',
            'BenutzerNummer' =>  '09011551',
            // 'Password' => 'Password'
            'Verbund' =>  'SWB',
            'TitelId' =>  '479128995',
            'Besteller' =>  'E',
        ];

        $urlsToTest = [
            "https://fltest.bsz-bw.de/flcgi/pflauftrag.pl",
            "https://zfl.bsz-bw.de/flcgi/pflauftrag.pl",
        ];
        foreach ($urlsToTest as $uri) {
            echo '<h2>Testing: ' . $uri . '</h2>';
            if (Debug::isInternal()) {
                $client = new Client();
                $client->setAdapter('\Laminas\Http\Client\Adapter\Curl')
                    ->setUri($uri)
                    ->setMethod('POST')
                    ->setOptions(['timeout' => 5])
                    ->setParameterPost($params)
                    // Schluck, ein Passwort im Code :-/
                    ->setAuth("boss", "Passworteingeben");

                $response = $client->send();
                var_dump($response->getBody());
            }
        }

        //avoid any templates being processed
        return $this->getResponse();
    }

    public function zflAuthAction()
    {
    }
}
