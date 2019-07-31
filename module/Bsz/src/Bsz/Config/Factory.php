<?php

/*
 * The MIT License
 *
 * Copyright 2016 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Bsz\Config;

use Interop\Container\ContainerInterface,
    Zend\Db\ResultSet\ResultSet;
/**
 * Description of Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{

    /**
     * 
     * @param ContainerInterface $container
     * @return \Bsz\Config\Client
     */
    public static function getClient(ContainerInterface $container)
    {
        $vufindconf = $container->get('VuFind\Config')->get('config')->toArray();
        $bszconf = $container->get('VuFind\Config')->get('bsz')->toArray();
        $searchconf = $container->get('VuFind\Config')->get('searches')->toArray();
        $sessContainer = new \Zend\Session\Container(
            'fernleihe', $container->get('VuFind\SessionManager')
        );
        
        $client = new Client(array_merge($vufindconf, $bszconf, $searchconf), true);
        $client->appendContainer($sessContainer);
        if ($client->isIsilSession()) {
            $libraries = $container->get('Bsz\Config\Libraries');
            $request = $container->get('Request');
            $client->setLibraries($libraries);
            $client->setRequest($request);
        }
        return $client;
    }

    /**
     * 
     * @param ContainerInterface $container
     * @return \Bsz\LibrariesTable
     */
    public static function getLibrariesTable(ContainerInterface $container)
    {
        # fetch mysql connection info out config
        $config = $container->get('VuFind\Config')->get('config');
        $adapterfactory = $container->get('VuFind\DbAdapterFactory');
        $database = $config->get('Database');
        $library = $database->get('db_libraries');
        $adapter = $adapterfactory->getAdapterFromConnectionString($library);
        $resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAYOBJECT, new Library());
        $librariesTable = new Libraries('libraries', $adapter, null, $resultSetPrototype);
        return $librariesTable;
    }  
    
    public static function getDedup(ContainerInterface $container) 
    {
        $config = $container->get('VuFind\Config')->get('config')->get('Index');
        $sesscontainer = new \Zend\Session\Container(
            'dedup', $container->get('VuFind\SessionManager')
        );
        $response = $container->get('Response');
        $cookie = $container->get('Request')->getCookie();
        return new Dedup($config, $sesscontainer, $response, $cookie);
    }
    
}

