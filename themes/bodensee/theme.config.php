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

$config = [
    'extends' => 'bootstrap3',
    'favicon' => '/themes/bodensee/images/favicon/default.ico',
    'js' => [
        'additions.js',
        'vendor/jquery.mark.min.js',
        'vendor/clipboard.min.js'
    ],
    'helpers' => [
        'factories' => [
            'Bsz\View\Helper\Bootstrap3\LayoutClass' => 'BszTheme\View\Helper\Bodensee\Factory::getLayoutClass',
            'Bsz\View\Helper\Root\OpenUrl' => 'BszTheme\View\Helper\Bodensee\Factory::getOpenUrl',
            'Bsz\View\Helper\Root\Record' => 'BszTheme\View\Helper\Bodensee\Factory::getRecord',
            'Bsz\View\Helper\Root\RecordLink' => 'BszTheme\View\Helper\Bodensee\Factory::getRecordLink',
            'Bsz\View\Helper\Root\Piwik' => 'BszTheme\View\Helper\Bodensee\Factory::getPiwik',
            'Bsz\View\Helper\Root\SearchTabs' => 'BszTheme\View\Helper\Bodensee\Factory::getSearchTabs',
            'Bsz\View\Helper\Root\SearchMemory' => 'BszTheme\View\Helper\Bodensee\Factory::getSearchMemory',
            'illform' => 'BszTheme\View\Helper\Bodensee\Factory::getIllForm',
            'BszTheme\View\Helper\Bodensee\Wayfless' => 'BszTheme\View\Helper\Bodensee\Factory::getWayfless'
        ],
        'aliases' => [
            'VuFind\View\Helper\Bootstrap3\LayoutClass' => 'Bsz\View\Helper\Bootstrap3\LayoutClass',
            'VuFind\View\Helper\Root\OpenUrl' => 'Bsz\View\Helper\Root\OpenUrl',
            'VuFind\View\Helper\Root\Record' => 'Bsz\View\Helper\Root\Record',
            'VuFind\View\Helper\Root\RecordLink' => 'Bsz\View\Helper\Root\RecordLink',
            'VuFind\View\Helper\Root\Piwik' => 'Bsz\View\Helper\Root\Piwik',
            'VuFind\View\Helper\Root\SearchTabs' => 'Bsz\View\Helper\Root\SearchTabs',
            'VuFind\View\Helper\Root\SearchMemory' => 'Bsz\View\Helper\Root\SearchMemory',
            'wayfless' => 'BszTheme\View\Helper\Bodensee\Wayfless'
        ]
    ]
];
return $config;
