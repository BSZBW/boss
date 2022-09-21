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
namespace BszTheme\View\Helper;

use Bsz\Config\Libraries as LibrariesTable;
use Bsz\Config\Library as LibraryRow;
use Interop\Container\ContainerInterface;

/**
 * Description of Factory
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{
    /**
     * Get Client View Helper
     *
     * @param ContainerInterface $container
     *
     * @return Client
     */
    public static function getClient(ContainerInterface $container)
    {
        $client = $container->get(\Bsz\Config\Client::class);
        return new Client($client);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return ClientAsset
     */
    public static function getClientAsset(ContainerInterface $container)
    {
        $client = $container->get(\Bsz\Config\Client::class);
        $tag = $client->getTag();
        $website = $client->getWebsite();

        $library = null;
        $libraries = $container->get('Bsz\Config\Libraries');
        if ($libraries instanceof LibrariesTable) {
            if ($client->isIsilSession() && $client->hasIsilSession()) {
                $isils = $client->getIsils();
                $library = $libraries->getFirstActive($isils);

                if ($library instanceof LibraryRow) {
                    $website = $library->getHomepage();
                }
            }
        }
        return new ClientAsset($tag, $website, $library);
    }

    /**
     * Get Interlending View Helper
     *
     * @param ContainerInterface $container
     *
     * @return Libraries
     */
    public static function getLibraries(ContainerInterface $container)
    {
        $libraries = $container->get('Bsz\Config\Libraries');
        return new Libraries($libraries);
    }
}
