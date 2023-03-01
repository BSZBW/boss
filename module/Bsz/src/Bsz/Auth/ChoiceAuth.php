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

use Bsz\Config\Library;
use VuFind\Exception\Auth as AuthException;

/**
 * Class ChoiceAuth
 * @package  Bsz\Auth
 * @category boss
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class ChoiceAuth extends \VuFind\Auth\ChoiceAuth
{
    protected $library;
    /**
     * Constructor
     *
     * @param \Zend\Session\Container $container Session container for retaining
     * user choices.
     */
    public function __construct(\Zend\Session\Container $container, Library $library = null)
    {
        parent::__construct($container);
        // Set up session container and load cached strategy (if found):
        $this->session = $container;
        $this->library = $library;

        $this->strategy = isset($this->session->auth_method)
            ? $this->session->auth_method : false;
    }

    /**
     * Set configuration; throw an exception if it is invalid.
     *
     * @param \Zend\Config\Config $config Configuration to set
     *
     * @throws AuthException
     * @return void
     */
    public function setConfig($config)
    {
        parent::setConfig($config);
        if ($this->library instanceof Library && $this->library->loginEnabled()) {
            $authMethods = $this->library->getAuth();
            $this->strategies = array_map([$this, 'map2Classnames'], $authMethods);
        } else {
            $this->strategies = array_map(
                'trim',
                explode(',', $config->ChoiceAuth->choice_order)
            );
        }
    }

    /**
     * @param $input
     *
     * @return string
     */
    private function map2ClassNames($input)
    {
        $output = trim($input);

        switch ($input) {
            case 'kohaauth': $output = 'koha';
            break;
            case 'adisauth': $output = 'adis';
            break;
        }
        return $output;
    }

    /**
     * Validate configuration parameters.  This is a support method for getConfig(),
     * so the configuration MUST be accessed using $this->config; do not call
     * $this->getConfig() from within this method!
     *
     * @throws AuthException
     * @return void
     */
    protected function validateConfig()
    {

    }
}
