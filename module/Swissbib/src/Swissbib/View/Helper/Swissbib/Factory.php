<?php
/**
 * Factory for view helpers related to the Swissbib theme.
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category swissbib VuFind2
 * @package  Swissbib\View\Helper\Swissbib
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace Swissbib\View\Helper\Swissbib;

use Zend\ServiceManager\ServiceManager;

use VuFind\View\Helper\Root\SearchParams;
use VuFind\View\Helper\Root\SearchOptions;
use VuFind\View\Helper\Root\SearchBox;

use Swissbib\VuFind\View\Helper\Root\Auth;
use Swissbib\VuFind\View\Helper\Root\SearchTabs;
use Swissbib\View\Helper\LayoutClass;
use Swissbib\View\Helper\IncludeTemplate;


/**
 * Factory for swissbib specific view helpers related to the Swissbib Theme.
 * these theme related static factory functions were refactored from Closures
 * which were part of the configuration. Because configuration can now be cached we have to write factory methods
 *
 * @category swissbib VuFind2
 * @package  Controller
 * @author   Guenter Hipler <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Factory
{


    public static function getRecordHelper(ServiceManager $sm)
    {
        return new \Swissbib\View\Helper\Record(
            $sm->getServiceLocator()->get('VuFind\Config')->get('config')
        );

    }

    public static function getFlashMessages(ServiceManager $sm)
    {
        $messenger = $sm->getServiceLocator()->get('ControllerPluginManager')
            ->get('FlashMessenger');

        return new \Swissbib\VuFind\View\Helper\Root\Flashmessages($messenger);

    }


    public static function getCitation(ServiceManager $sm)
    {
        return new \Swissbib\VuFind\View\Helper\Root\Citation(
            $sm->getServiceLocator()->get('VuFind\DateConverter')
        );

    }

    public static function getRecordLink(ServiceManager $sm)
    {
        return new \Swissbib\View\Helper\RecordLink(
            $sm->getServiceLocator()->get('VuFind\RecordRouter')
        );

    }

    public static function getExtendedLastSearchLink(ServiceManager $sm)
    {
        return new \Swissbib\View\Helper\GetExtendedLastSearchLink(
            $sm->getServiceLocator()->get('VuFind\Search\Memory')
        );

    }

    /**
     * Construct the Auth helper as an extension of the VuFind Core Auth helper
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Auth
     */
    public static function getAuth(ServiceManager $sm)
    {
        return new Auth($sm->getServiceLocator()->get('VuFind\AuthManager'));
    }

    /**
     * @param ServiceManager $sm
     * @return LayoutClass
     */
    public static function getLayoutClass(ServiceManager $sm) {
        $config = $sm->getServiceLocator()->get('VuFind\Config')->get('config');
        $left = !isset($config->Site->sidebarOnLeft)
            ? false : $config->Site->sidebarOnLeft;

        return new LayoutClass($left);
    }

    /**
     * @param ServiceManager $sm
     *
     * @return SearchTabs
     */
    public static function getSearchTabs(ServiceManager $sm)
    {
        return new SearchTabs(
            $sm->getServiceLocator()->get('Swissbib\SearchResultsPluginManager'),
            $sm->getServiceLocator()->get('VuFind\Config')->get('config')->toArray(),
            $sm->get('url')
        );
    }

    /**
     * @param ServiceManager $sm Service manager.
     *
     * @return SearchParams
     */
    public static function getSearchParams(ServiceManager $sm)
    {
        return new SearchParams(
            $sm->getServiceLocator()->get('Swissbib\SearchParamsPluginManager')
        );
    }

    /**
     * @param ServiceManager $sm Service manager.
     *
     * @return SearchOptions
     */
    public static function getSearchOptions(ServiceManager $sm)
    {
        return new SearchOptions(
            $sm->getServiceLocator()->get('Swissbib\SearchOptionsPluginManager')
        );
    }

    /**
     * @param ServiceManager $sm Service manager.
     *
     * @return SearchBox
     */
    public static function getSearchBox(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config');
        return new SearchBox(
            $sm->getServiceLocator()->get('Swissbib\SearchOptionsPluginManager'),
            $config->get('searchbox')->toArray()
        );
    }

    /**
     * @param ServiceManager $sm Service manager.
     *
     * @return IncludeTemplate
     */
    public static function getIncludeTemplate(ServiceManager $sm)
    {
        return new IncludeTemplate();
    }
}
