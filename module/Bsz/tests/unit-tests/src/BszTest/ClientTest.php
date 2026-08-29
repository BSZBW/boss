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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 */
namespace BszTest;

use Bsz\Config\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function getBasicConfig()
    {
        return new Client([
            'Site' => [
                'isil' => 'DE-666,DE-667',
                'website' => 'https://www.example.com',
                'website_google' => 'https://www.google.com',
                'website_lang' => 'https://foo.bar/%lang%',
                'url' => 'foo.bar.com'
            ],
            'System' => [],
            'OpenUrl' => [],
            'Footer' => [],
            'Switches' => [
                'isil_session' => false
            ],
            'FooterLinks' => []
        ], true);
    }

    public function getClient($config)
    {
        return $client = new Client($config->toArray());
    }

    public function testClientCreatedSuccessfull()
    {
        $config = $this->getBasicConfig();
        $client = $this->getClient($config);
        $this->assertEquals(get_class($client), 'Bsz\Config\Client');
    }

    public function testIsilsParsing()
    {
        $config = $this->getBasicConfig();
        $client = $this->getClient($config);
        $isils = $client->getIsils();
        $this->assertIsArray($isils);
        $this->assertEquals((string)$client, array_shift($isils));
    }

    public function testDifferentWebsites()
    {
        $config = $this->getBasicConfig();
        $client = $this->getClient($config);
        $this->assertEquals($client->getWebsite(), 'https://www.example.com');
        $this->assertEquals($client->getWebsite('google'), 'https://www.google.com');
        $this->assertEquals($client->getWebsite('lang', 'en'), 'https://foo.bar/en');
    }

    public function testTagParsing()
    {
        $config = $this->getBasicConfig();
        $client = $this->getClient($config);
        $this->assertEquals($client->getTag(), 'foo');
        $config['Site']['url'] = 'https://bar.boss.bsz-bw.de';
        $client = $this->getClient($config);
        $this->assertEquals($client->getTag(), 'bar');
    }

    public function testDefaultFooterLinks()
    {
        $config = $this->getBasicConfig();
        $client = $this->getClient($config);
        //$links1 = $client->getFooterLinks(1);
        $links2 = $client->getFooterLinks(2);
        $links3 = $client->getFooterLinks(3);
        $this->assertEquals($links2[0], '/Search/History');
        $this->assertEquals($links2[1], '/Search/Advanced');
        $this->assertEquals($links3[0], '/Bsz/Privacy');
    }
}
