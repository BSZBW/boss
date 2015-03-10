<?php
namespace Swissbib\VuFind\View\Helper\Bootstrap3;

use Zend\View\Helper\AbstractHelper, Zend\Mvc\Controller\Plugin\FlashMessenger;
use VuFind\View\Helper\Root\Flashmessages;

/**
 * [...description of the type ...]
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 10/03/15
 * Time: 18:35 AM
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
 * @category swissbib_VuFind2
 * @package  [...package name...]
 * @author   Biljana Radivojevic  <biljana.radivojevic@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */

class Flashmessages extends \VuFind\View\Helper\Root\Flashmessages
{
    /**
     * Get the CSS class to correspond with a messenger namespace
     *
     * @param string $ns Namespace
     *
     * @return string
     */
    protected function getClassForNamespace($ns)
    {
        $cssClassName = 'alert alert-';
        if ($ns == 'error') {
            $ns = 'danger';
        } elseif ($ns == 'info') {
            $cssClassName = 'status_';
        }

        return $cssClassName . $ns;
    }

}