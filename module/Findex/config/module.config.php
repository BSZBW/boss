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

namespace Bsz\Module\Config;

use Bsz\Controller\Factory;
use Bsz\Route\RouteGenerator;

$config = [

    'vufind' => [
        'plugin_managers' => [
            'recorddriver'  => [
                'factories' => [
                    'Findex\RecordDriver\SolrFindexMarc' => 'Findex\RecordDriver\Factory',
                ],
                'aliases' => [
                    'SolrFindexMarc'   => 'Findex\RecordDriver\SolrFindexMarc',
                ],
                'delegators' => [
                    'Findex\RecordDriver\SolrFindexMarc' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                ]
            ],
            'recordtab' => [
                'factories' => [
                    'Findex\RecordTab\FindexLibraries' => 'Findex\RecordTab\Factory::getLibraries',
                    'Findex\RecordTab\FindexVolumes' => 'Findex\RecordTab\Factory::getVolumes',
                    'Findex\RecordTab\FindexArticles' => 'Findex\RecordTab\Factory::getArticles',

                ],
                'aliases' => [
                    'FindexLibraries' => 'Findex\RecordTab\FindexLibraries',
                    'FindexVolumes' => 'Findex\RecordTab\FindexVolumes',
                    'FindexArticles' => 'Findex\RecordTab\FindexArticles',

                ]
            ],
        ],
    ]

];
return $config;
