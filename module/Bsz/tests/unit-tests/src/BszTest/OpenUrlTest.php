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

namespace BszTest;

use Bsz\Parser\OpenUrl;
use PHPUnit\Framework\TestCase;
use Laminas\Config\Config;

class OpenUrlTest extends TestCase
{
    private function getBaseConfig(): Config
    {
        $config = new Config([
            'Form' => [
                'rft_au' => 'AufsatzAutor',
                'rft_aucorp' => 'Verfasser',
                'rft_isbn' => 'Isbn',
                'rft_title' => 'Titel',
                'rft_btitle' => 'Titel',
                'rft_jtitle' => 'Titel',
                'rft_genre' => 'genre',
                'rft_place' => 'EOrt'
            ]

        ]);
        return $config;
    }

    private function getBaseParams(): array
    {
        return [
            'rft_au' => 'Max Mustermann',
            'rft_genre' => 'book',
            'rft_title' => 'Repetitorium der höheren Mathematik',
            'rft_jtitle' => 'Zeitschriftentitel'
        ];
    }


    public function testParser()
    {
        $config = $this->getBaseConfig();
        $parser = new OpenUrl($config);
        $params = $this->getBaseParams();
        $parser->setParams($params);
        $result = $parser->map2Form();

        $this->assertArrayHasKey('Verfasser', $result);
        $this->assertArrayHasKey('Titel', $result);
        $this->assertEquals($result['Titel'], 'Zeitschriftentitel');
    }

    public function testDotKeys()
    {
        $config = $this->getBaseConfig();
        $parser = new OpenUrl($config);
        $params = ['rft.au' => 'Max Mustermann'];
        $parser->setParams($params);
        $result = $parser->map2Form();

        $this->assertArrayHasKey('AufsatzAutor', $result);

    }

}
