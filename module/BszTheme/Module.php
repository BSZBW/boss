<?php
/**
 * Module definition for the VuFind theme system.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2013.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category BSZ
 * @package  Theme
 * @author   Cornelius Amzar
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development
 */
namespace BszTheme;

/**
 * Bsz theme adaption
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Module
{
    /**
     * Get autoloader configuration
     *
     * @return void
     */
    public function getAutoloaderConfig()
    {
        return [
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ],
            ],
        ];
    }

    /**
     * Here, we override the VuFindTheme module with our own module
     * @return []
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                \VuFindTheme\ThemeInfo::class => "\BszTheme\ThemeInfoFactory::getThemeInfo",
            ],
        ];
    }

    /**
     * Get view helper configuration.
     *
     * @return array
     */
    public function getViewHelperConfig()
    {
        return [
            'factories' => [
                'client' =>         'BszTheme\View\Helper\Factory::getClient',
                'clientAsset' =>    'BszTheme\View\Helper\Factory::getClientAsset',
                'IllForm' =>        'BszTheme\View\Helper\Bodensee\Factory::getIllForm',
                'libraries' =>      'BszTheme\View\Helper\Factory::getLibraries',
                'mapongo' =>      'BszTheme\View\Helper\Bodensee\Factory::getMapongo',
            ],
            'invokables' => [
                'string' => 'BszTheme\View\Helper\StringHelper',
                'abbrev' => 'BszTheme\View\Helper\Bodensee\Abbrev',
                'showmore' => 'BszTheme\View\Helper\Bodensee\Showmore',
                'clipboard' => 'BszTheme\View\Helper\Bodensee\Clipboard'
            ],
        ];
    }
}
