<?php
/**
 * Helper class for managing bootstrap theme's high-level (body vs. sidebar) page
 * layout.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2011.
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
 * @category VuFind2
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace BszTheme\View\Helper\Bodensee;

/**
 * Helper class for managing bootstrap theme's high-level (body vs. sidebar) page
 * layout.
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class LayoutClass extends \VuFind\View\Helper\Bootstrap3\LayoutClass
{
    /**
     * Helper to allow easily configurable page layout -- given a broad class
     * name, return appropriate CSS classes to lay out the page according to
     * the current configuration file settings.
     *
     * @param string $class Type of class to return ('mainbody' or 'sidebar')
     *
     * @return string       CSS classes to apply
     */
    public function __invoke($class)
    {
        switch ($class) {
        case 'maximized':
            return 'col-sm-12 col-md-11 col-lg-11';
        case 'mainbody_foldout':
            return 'col-sm-12 col-md-9';
        case 'mainbody':
            return $this->sidebarOnLeft
                ? 'col-sm-8 col-sm-push-4 col-md-9 col-md-push-3'
                : 'col-sm-8 col-md-9';
        case 'mainbody_details':
            return $this->sidebarOnLeft
                ? 'col-sm-12'
                : 'col-sm-12';
            break;
        case 'sidebar':
            return $this->sidebarOnLeft
                ? 'sidebar col-sm-4 col-sm-pull-8 col-md-3 col-md-pull-9 hidden-print'
                : 'sidebar col-sm-4 col-md-3 hidden-print';
        case 'offcanvas-row':
            if (!$this->offcanvas) {
                return "";
            }
            return $this->sidebarOnLeft
                ? 'offcanvas offcanvas-left flip'
                : 'offcanvas offcanvas-right flip';
        }
    }
}
