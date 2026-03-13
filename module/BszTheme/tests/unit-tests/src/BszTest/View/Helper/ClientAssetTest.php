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
namespace BszTest\View\Helper;

use BszTest\LibraryTest;
use BszTheme\View\Helper\ClientAsset;
use PHPUnit\Framework\TestCase;

/**
 * Class ClientAssetTest
 * @category boss
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class ClientAssetTest extends TestCase
{
    public function testSimpleLogo()
    {
        $asset = new ClientAsset('swb', 'http://foo.bar');
        $this->assertEquals($asset->getLogo(), 'logo/swb.svg');
    }

    public function testLibraryLogoViaSigel()
    {
        $libraryTest = new LibraryTest('');
        $library = $libraryTest->getLibrary();
        $asset = new ClientAsset('swb', 'http://foo.bar', $library);
        $this->assertEquals($asset->getLogo(), 'logo/libraries/16.jpg');

    }
}


