<?php
/**
 * EDS Record Controller
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind2
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Bsz\Controller;

/**
 * EDS Record Controller
 *
 * @category VuFind2
 * @package  Controller
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class EdsrecordController extends \VuFind\Controller\EdsrecordController
{
    /**
     * This action needs to be overwritten as we do not use permissions.ini and
     * have no login at the moment. boss2
     *
     * @return mixed
     */
    public function pdfAction()
    {
        $driver = $this->loadRecord();

        $url =$driver->getPdfLink();
        // user has no access to url
        if (!$url) {
            return $this->redirectToRecord('', 'PDFUnavailable');
        } else {
            return $this->redirect()->toUrl($url);
        }
    }

    /**
     * Display a particular tab.
     *
     * @param string $tab  Name of tab to display
     * @param bool   $ajax Are we in AJAX mode?
     *
     * @return mixed
     */
    protected function showTab($tab, $ajax = false)
    {
        if ($tab == 'PDFUnavailable') {
            $this->flashMessenger()->addInfoMessage('Elektronischer Volltext. Online-Zugang im Hochschulnetz, von außerhalb nur für Hochschulangehörige via VPN');
            $tab = 'Description';
        }
        $view = parent::showTab($tab, $ajax);
        return $view;
    }

    public function getBreadcrumb()
    {
        return parent::getBreadcrumb();
    }

    public function getUniqueID()
    {
        return parent::getUniqueID();
    }
}
