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

use BszTheme\ThemeInfo;
use PHPUnit\Framework\TestCase;

class ThemeInfoTest extends TestCase
{
    public function testBszExtensions()
    {
        $ti = new ThemeInfo(APPLICATION_PATH . '/themes', 'bodensee', 'wlb');
        $info = $ti->getThemeInfo();

        $this->assertArrayHasKey('bodensee', $info);
        $this->assertArrayHasKey('css', $info['bodensee']);
        $this->assertArrayHasKey('favicon', $info['bodensee']);
        $this->assertEquals(count($info['bodensee']['css']), 1);
        $this->assertEquals($info['bodensee']['css'][0]['file'], 'wlb.css');
        $this->assertArrayHasKey('js', $info['bodensee']);
        $this->assertTrue(in_array('additions.js', $info['bodensee']['js']));
    }
}
