<?php
/*
 * Copyright 2022 (C) Bibliotheksservice-Zentrum Baden-
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

namespace Findex\RecordTab;

use Bsz\RecordTab\Articles;
use Bsz\RecordTab\Volumes;
use Interop\Container\ContainerInterface;
use Laminas\Http\PhpEnvironment\Request;

class Factory
{
    /**
     * Factory for libraries tab
     *
     * @param ContainerInterface $container
     *
     * @return FindexLibraries
     */
    public static function getLibraries(ContainerInterface $container)
    {
        $libraries = $container->get('Bsz\Config\Libraries');
        $client = $container->get('Bsz\Config\Client');
        return new FindexLibraries($libraries, $client);
    }
    /**
     * Factory for volumes tab
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return FindexVolumes
     */
    public static function getVolumes(ContainerInterface $container)
    {
        $client = $container->get('Bsz\Config\Client');
        $isils = $client->getIsils();

        $volumes = new FindexVolumes(
            $container->get(\VuFindSearch\Service::class),
            $isils
        );
        return $volumes;
    }

    /**
     * Factory for articles tab
     *
     * @param ContainerInterface $container
     *
     * @return FindexArticles
     */
    public static function getArticles(ContainerInterface $container)
    {
        $client = $container->get('Bsz\Config\Client');
        $isils = $client->getIsils();

        $articles = new FindexArticles(
            $container->get(\VuFindSearch\Service::class),
            $isils
        );
        return $articles;
    }
}