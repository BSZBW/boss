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

use Interop\Container\ContainerInterface;
use VuFind\Auth\AlmaDatabase;
use VuFind\Auth\CAS;
use Bsz\Auth\ChoiceAuth;
use Bsz\Auth\ChoiceAuthFactory;
use VuFind\Auth\Database;
use VuFind\Auth\Email;
use VuFind\Auth\EmailFactory;
use VuFind\Auth\Facebook;
use VuFind\Auth\FacebookFactory;
use VuFind\Auth\ILS;
use VuFind\Auth\IlsFactory;
use VuFind\Auth\LDAP;
use VuFind\Auth\MultiAuth;
use VuFind\Auth\MultiAuthFactory;
use VuFind\Auth\MultiILS;
use Bsz\Auth\Shibboleth;
use Bsz\Auth\Factory;
use VuFind\Auth\SIP2;
use Zend\ServiceManager\Factory\InvokableFactory;

class PluginManager extends \VuFind\Auth\PluginManager
{
    /**
     * Default plugin aliases.
     *
     * @var array
     */
    protected $aliases = [
        'almadatabase' => AlmaDatabase::class,
        'cas' => CAS::class,
        'choiceauth' => ChoiceAuth::class,
        'database' => Database::class,
        'email' => Email::class,
        'facebook' => Facebook::class,
        'ils' => ILS::class,
        'ldap' => LDAP::class,
        'multiauth' => MultiAuth::class,
        'multiils' => MultiILS::class,
        'shibboleth' => Shibboleth::class,
        'sip2' => SIP2::class,
        // for legacy 1.x compatibility
        'db' => Database::class,
        'sip' => SIP2::class,
        'koha' => Koha::class,
        'ncip' => NCIP::class,
    ];

    /**
     * Default plugin factories.
     *
     * @var array
     */
    protected $factories = [
        AlmaDatabase::class => ILSFactory::class,
        CAS::class => InvokableFactory::class,
        ChoiceAuth::class => ChoiceAuthFactory::class,
        Database::class => InvokableFactory::class,
        Email::class => EmailFactory::class,
        Facebook::class => FacebookFactory::class,
        ILS::class => ILSFactory::class,
        LDAP::class => InvokableFactory::class,
        MultiAuth::class => MultiAuthFactory::class,
        MultiILS::class => ILSFactory::class,
        Shibboleth::class => Factory::class,
        SIP2::class => InvokableFactory::class,
        Koha::class => Factory::class,
        NCIP::class => Factory::class
    ];

}