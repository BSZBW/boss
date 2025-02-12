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

use Bsz\Config\Library;
use PHPUnit\Framework\TestCase;

class LibraryTest extends TestCase
{
    public function getLibrary() : Library
    {
        $library = new Library();
        $data = $this->getDefaultData();
        $library->exchangeArray($data);
        return $library;
    }

    private function getDefaultData() : array
    {
        return [
            'isil' => 'DE-16',
            'name' => 'Testbibliothek',
            'sigel' => 16,
            'is_live' => false,
            'is_boss' => true,
            'homepage' => 'http://foo.bar.com',
            'email' => '',
            'isil_availability' => 'DE-16-1',
            'regex' => '/@[a-zA-z0-9\.-]*/',
            'hide_costs' => false
        ];
    }

    public function testBasicLibrary()
    {
        $library = $this->getLibrary();
        $this->assertEquals($library->getIsil(), 'DE-16');
        $this->assertFileExists('themes/bodensee/images/'.$library->getLogo());
        $this->assertIsArray($library->getIsilAvailability());
    }

    public function testRegex()
    {
        $library = $this->getLibrary();
        $regex = $library->getRegex();
        $this->assertEquals(preg_replace($regex,'', 'foo.bar@institution.com'), 'foo.bar');
    }
}
