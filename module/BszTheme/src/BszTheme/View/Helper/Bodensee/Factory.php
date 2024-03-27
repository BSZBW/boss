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
namespace BszTheme\View\Helper\Bodensee;

use Bsz\Config\Client;
use Bsz\Config\Library;
use Bsz\Exception;
use Interop\Container\ContainerInterface;
use VuFind\Config\Locator;

/**
 * Factory for Bootstrap view helpers.
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 *
 * @codeCoverageIgnore
 */
class Factory
{
    /**
     * Construct the Flashmessages helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return Flashmessages
     */
    public static function getFlashmessages(ContainerInterface $container)
    {
        $messenger = $container->get('ControllerPluginManager')
            ->get('FlashMessenger');
        return new Flashmessages($messenger);
    }

    /**
     * Construct the LayoutClass helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return LayoutClass
     */
    public static function getLayoutClass(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config');
        $left = !isset($config->Site->sidebarOnLeft)
            ? false : $config->Site->sidebarOnLeft;
        $mirror = !isset($config->Site->mirrorSidebarInRTL)
            ? true : $config->Site->mirrorSidebarInRTL;
        $offcanvas = !isset($config->Site->offcanvas)
            ? false : $config->Site->offcanvas;
        // The right-to-left setting is injected into the layout by the Bootstrapper;
        // pull it back out here to avoid duplicate effort, then use it to apply
        // the mirror setting appropriately.
        $layout = $container->get('ViewManager')->getViewModel();
        if ($layout->rtl && !$mirror) {
            $left = !$left;
        }
        return new LayoutClass($left, $offcanvas);
    }

    /**
     * Construct the OpenUrl helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return OpenUrl
     */
    public static function getOpenUrl(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config');
        $client = $container->get('Bsz\Config\Client');
        $isils = $client->getIsils();
        $openUrlRules = json_decode(
            file_get_contents(
                Locator::getConfigPath('OpenUrlRules.json')
            ),
            true
        );
        $resolverPluginManager =
            $container->get('VuFind\ResolverDriverPluginManager');
        return new OpenUrl(
            $container->get('ViewHelperManager')->get('context'),
            $openUrlRules,
            $resolverPluginManager,
            isset($config->OpenURL) ? $config->OpenURL : null,
            !empty($isils) ? array_shift($isils) : null
        );
    }

    /**
     * Construct the Record helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return Record
     */
    public static function getRecord(ContainerInterface $container)
    {
        return new Record(
            $container->get('VuFind\Config')->get('config'),
            $container->get('VuFind\Config')->get('FormatIcons'),
            $container->get(Client::class)->getIsilAvailability()
        );
    }

    /**
     * Construct the RecordLink helper.
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return RecordLink
     * @throws Exception
     */
    public static function getRecordLink(ContainerInterface $container)
    {
        $client = $container->get(Client::class);
        $libraries = $container->get('Bsz\Config\Libraries');
        $opacUrl = null;

        $library = $libraries->getFirstActive($client->getIsils());
        if ($library instanceof Library) {
            $opacUrl = $library->getOpacUrl() !== null ? $library->getOpacUrl() : null;
        }

        return new RecordLink(
            $container->get('VuFind\RecordRouter'),
            $container->get('VuFind\Config')->get('bsz'),
            $opacUrl
        );
    }

    /**
     * Construct the GetLastSearchLink helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return GetLastSearchLink
     */
    public static function getSearchMemory(ContainerInterface $container)
    {
        return new SearchMemory(
            $container->get('VuFind\Search\Memory')
        );
    }

    /**
     * Construct the Piwik helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return Piwik
     */
    public static function getPiwik(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config')->get('Piwik');
        $url = isset($config->url) ? $config->url : false;
        $siteId = isset($config->site_id) ? $config->site_id : 1;
        $globalSiteId = isset($config->site_id_global) ? $config->site_id_global : 0;
        $groupSiteId = isset($config->site_id_group) ? $config->site_id_group : 0;
        $customVars = isset($config->custom_variables)
            ? $config->custom_variables
            : false;
        return new Piwik(
            $url,
            $siteId,
            $customVars,
            $globalSiteId,
            $groupSiteId
        );
    }

    /**
     * Construct the SearchTabs helper.
     *
     * @param ContainerInterface$container Service manager.
     *
     * @return SearchTabs
     */
    public static function getSearchTabs(ContainerInterface $container)
    {
        return new SearchTabs(
            $container->get('VuFind\SearchResultsPluginManager'),
            $container->get('ViewHelperManager')->get('url'),
            $container->get('VuFind\SearchTabsHelper')
        );
    }

    /**
     * @param ContainerInterface $container
     * @return IllForm
     */
    public static function getIllForm(ContainerInterface $container)
    {
        $request = $container->get('request');
        // params from form submission
        $params = $request->getPost()->toArray();
        // params from open url
        $openUrlParams = $request->getQuery()->toArray();
        $parser = $container->get('Bsz\Parser\OpenUrl');
        $parser->setParams($openUrlParams);
        // mapped openURL params
        $formParams = $parser->map2Form();
        // merge both param sets
        $mergedParams = array_merge($formParams, $params);
        return new IllForm($mergedParams);
    }

    public static function getMapongo(ContainerInterface $container)
    {
        $client = $container->get('Bsz\Config\Client');
        return new Mapongo(
            $client->get('Mapongo')
        );
    }

    public static function getWayfless(ContainerInterface $container)
    {
        $config = $container->get('Bsz\Config\Client');
        return new Wayfless(
            $config->get('WAYFless'),
            $config->getTag()
        );
    }

    public static function getBibliographyIcon(ContainerInterface $container): BibliographyIcon
    {
        return new BibliographyIcon(
            $container->get('Bsz\Config\Client')
        );
    }

    public static function getIdVerifier(ContainerInterface $container)
    {
        return new IdVerifier(
            $container->get("VuFind\Search")
        );
    }

    public static function getRecordDetails(ContainerInterface $container)
    {
        return new RecordDetailsHelper($container);
    }

    public static function getRecordDetailList(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('RecordDetails');
        return new RecordDetailList($config);
    }


}
