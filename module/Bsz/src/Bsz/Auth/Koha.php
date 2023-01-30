<?php
/*
 * Copyright 2023 (C) Bibliotheksservice-Zentrum Baden-
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

namespace Bsz\Auth;

use Bsz\Config\Libraries;

class Koha extends \VuFind\Auth\AbstractBase
{

    protected $libraries;
    protected $isil;
    /**
     * Constructor
     *
     * @param \Zend\Session\ManagerInterface $sessionManager Session manager
     */
    public function __construct(
        \Zend\Session\ManagerInterface $sessionManager,
        Libraries $libraries,
                                       $isil)
    {
        $this->sessionManager = $sessionManager;
        $this->libraries = $libraries;
        $this->isil = $isil;
    }
    /**
     * @inheritDoc
     */
    public function authenticate($request)
    {
        // TODO: Implement authenticate() method.
    }
}