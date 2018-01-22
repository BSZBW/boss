<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
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
 */

namespace Bsz\Search\Options;
use \Zend\ServiceManager\ServiceManager;

/**
 * BSz Search Options Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory {
    /**
     * Factory for Interlending options object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Solr
     */
    public static function getInterlending(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config');
        $Client = $sm->getServiceLocator()->get('Bsz\Client');
        $options = new \Bsz\Search\Interlending\Options($config, $Client);
        return $options;
    }
    /**
     * Factory for Interlending options object.
     *
     * @param ServiceManager $sm Service manager.
     *
     * @return Solr
     */
    public static function getFis(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config');
        $Client = $sm->getServiceLocator()->get('Bsz\Client');
        $options = new \Bsz\Search\Fis\Options($config, $Client);
        return $options;
    }
    
    /**
     * Return modified vrsion of Solr Options with Client object
     * @param ServiceManager $sm
     * @return \Bsz\Search\Solr\Options
     */
    public static function getSolr(ServiceManager $sm)
    {
        $config = $sm->getServiceLocator()->get('VuFind\Config');
        $Client = $sm->getServiceLocator()->get('bsz\client');
        $options = new \Bsz\Search\Solr\Options($config, $Client);
        return $options;
    }
}