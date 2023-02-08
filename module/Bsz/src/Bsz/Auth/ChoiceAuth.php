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

/**
 * Class ChoiceAuth
 * @package  Bsz\Auth
 * @category boss
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class ChoiceAuth extends \VuFind\Auth\ChoiceAuth
{
    /**
     * Constructor
     *
     * @param \Zend\Session\Container $container Session container for retaining
     * user choices.
     */
    public function __construct(\Zend\Session\Container $container)
    {
        // Set up session container and load cached strategy (if found):
        $this->session = $container;
        $this->strategy = isset($this->session->auth_method)
            ? $this->session->auth_method : false;
    }

}