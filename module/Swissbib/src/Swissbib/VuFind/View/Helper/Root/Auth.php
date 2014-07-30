<?php
/**
 * swissbib Authentication view helper
 *
 * PHP version 5
 *
 * Copyright (C) project swissbib, University Library Basel, Switzerland
 * http://www.swissbib.org  / http://www.swissbib.ch / http://www.ub.unibas.ch
 *
 * Date: 7/22/14
 * Time: 4:49 PM
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
 * @author   Guenter Hipler  <guenter.hipler@unibas.ch>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://www.swissbib.org
 */
namespace Swissbib\VuFind\View\Helper\Root;
use Zend\View\Exception\RuntimeException;
use VuFind\View\Helper\Root\Auth as VFAuthHelper;

/**
 * Authentication view helper
 *
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Auth extends VFAuthHelper
{

    /**
     * Constructor
     *
     * @param \VuFind\Auth\Manager $manager Authentication manager
     */
    public function __construct(\VuFind\Auth\Manager $manager)
    {
        parent::__construct($manager);
    }

    /**
     * @return \VuFind\Auth\AbstractBase
     */
    public function getLoginTargets()
    {
        return $this->getManager()->getLoginTargets();
    }

}
