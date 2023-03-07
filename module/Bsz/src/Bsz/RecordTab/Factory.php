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
namespace Bsz\RecordTab;

use Bsz\Config\Client;
use Bsz\ILL\Logic;
use Interop\Container\ContainerInterface;
use Zend\Http\PhpEnvironment\Request;
use Zend\Session\AbstractContainer as SessionContainer;
use Zend\Session\SessionManager as SessionManager;

/**
 * Description of Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{
    /**
     * Factory for volumes tab
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return Volumes
     */
    public static function getVolumes(ContainerInterface $container)
    {
        $last = '';
        if (isset($_SESSION['Search']['last'])) {
            $last = urldecode($_SESSION['Search']['last']);
        }
        $isils = [];
        if (strpos($last, 'consortium=FL') === false
            && strpos($last, 'consortium=ZDB') === false
        ) {
            $client = $container->get('Bsz\Config\Client');
            $isils = $client->getIsils();
        }

        $volumes = new Volumes($container->get('VuFind\SearchRunner'), $isils);

        return $volumes;
    }

    /**
     * Factory for articles tab
     *
     * @param ContainerInterface $container
     *
     * @return Articles
     */
    public static function getArticles(ContainerInterface $container)
    {
        $last = '';
        if (isset($_SESSION['Search']['last'])) {
            $last = urldecode($_SESSION['Search']['last']);
        }
        $isils = [];
        if (strpos($last, 'consortium=FL') === false
            && strpos($last, 'consortium=ZDB') === false
        ) {
            $client = $container->get('Bsz\Config\Client');
            $isils = $client->getIsils();
        }

        $articles = new Articles($container->get('VuFind\SearchRunner'), $isils);
        $request = new Request();
        $url = strtolower($request->getUriString());
        return $articles;
    }

    /**
     * Factory for libraries tab
     *
     * @param ContainerInterface $container
     * @return Libraries
     */
    public static function getLibraries(ContainerInterface $container)
    {
        $libraries = $container->get('Bsz\Config\Libraries');
        $client = $container->get('Bsz\Config\Client');
        $swbonly = $client->getTag() === 'swb' ?? false;
        return new Libraries($libraries, !$client->is('disable_library_tab'), $swbonly);
    }

    /**
     * Factory for description tab
     *
     * @param ContainerInterface $container
     * @return Description
     */
    public static function getDescription(ContainerInterface $container)
    {
        $client = $container->get('Bsz\Config\Client');
        return new Description(!$client->is('disable_description_tab'));
    }

    /**
     * Factory for HoldingsILS tab plugin.
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return HoldingsILS
     */
    public static function getHoldingsILS(ContainerInterface $container)
    {
        // If VuFind is configured to suppress the holdings tab when the
        // ILS driver specifies no holdings, we need to pass in a connection
        // object:
        $config = $container->get('VuFind\Config')->get('config');
        if (isset($config->Site->hideHoldingsTabWhenEmpty) && $config->Site->hideHoldingsTabWhenEmpty
        ) {
            $catalog = $container->get('VuFind\ILSConnection');
        } else {
            $catalog = false;
        }
        return new HoldingsILS($catalog);
    }

    /**
     * @param ContainerInterface $container
     * @return InterlibraryLoan
     */
    public static function getInterlibraryLoan(ContainerInterface $container)
    {
        $sm = $container->get(SessionManager::class);
        $search = $sm->getStorage()->offsetGet('Search');
        $lastsearch = $search ? $search->offsetGet('last') : '';
        $lastsearch = urldecode($lastsearch);

        // we still don't have a solution to hide the tab in local view.
        $illmode = true;

        if (substr_count($lastsearch, 'consortium:"FL"') > 0 ||
            substr_count($lastsearch, 'consortium:"ZDB"') > 0
        ) {
            $illmode = true;
        }
        $client = $container->get(Client::class);
        $library = $client->getLibrary();
        $dienst = $client->getTag() === 'fl-dienst';

        $orderid = '';
        if (isset($_SESSION['fernleihe']['bestellid'])) {
            $orderid = $_SESSION['fernleihe']['bestellid'];
        }

        return new InterlibraryLoan($container->get(Logic::class), $library, $illmode, $dienst, $orderid);
    }
}
