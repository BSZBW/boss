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
    'extends' => 'bsz',
    'js' => [
        'hds_common.js',
    ],
        /**
         * Html elements can be made sticky which means that they don't leave the screen on scrolling.
         * You can make an element sticky by adding an array with the css selector to stickyElements.
         * Warning! The order of the entries in the config will be used to order the elements while they are sticky.
         * If you want to add extra classes to some child elements of sticky elements you can add an array with their
         * css selectors and the classes to stickyChildrenClasses. The default class is "hidden".
         * You can also add "min-width" and "max-width" to the configs so that the effect only applies on specific
         * screen sizes.
         * Examples:
         */
    'stickyElements' => [
    // Navbar Banner on non-mobile screens
    ["selector" => ".banner.container.navbar", "min-width" => 768],
    // Searchbox on search home page
    ["selector" => ".searchHomeContent"],
    // Searchbox on other pages
    ["selector" => ".search.container.navbar"],
    // Breadcrumbs on non-mobile screens
    //["selector" => ".breadcrumbs", "min-width" => 768]
    ],
    'stickyChildrenClasses' => [
    // Hide search tab selection on mobile screens
    ["selector" => ".searchForm > .nav.nav-tabs", "class" => "hidden", "max-width" => 767]
    ],
    'doctype' => 'HTML5',
];
return $config;
