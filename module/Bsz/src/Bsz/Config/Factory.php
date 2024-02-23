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

use Interop\Container\ContainerInterface;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Session\Container as SessionContainer;

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
     * @return Client
     */
    public static function getClient(ContainerInterface $container)
    {
        $tmp = $container->get('VuFind\Config')->get('config')->toArray();
        $neededSections = ['Site', 'System', 'OpenUrl', 'Index'];

        $vufindconf = [];
        foreach ($tmp as $section => $content) {
            if (in_array($section, $neededSections)) {
                $vufindconf[$section] = $content;
            }
        }

        $bszconf = $container->get('VuFind\Config')->get('bsz')->toArray();

        $client = new Client(array_merge($vufindconf, $bszconf), true);

        // Session is needed to fetch ISILs out of session
        $sessionContainer = new SessionContainer(
            'fernleihe',
            $container->get('VuFind\SessionManager')
        );
        $client->attachSessionContainer($sessionContainer);
        // Libraries object is needed to obtain current libraries
        $libraries = $container->get('Bsz\Config\Libraries');
        $client->attachLibraries($libraries);
        // Request is needed to work with cookies
        $request = $container->get('Request');
        $client->attachRequest($request);

        return $client;
    }

    /**
     * @param ContainerInterface $container
     *
     * @return Libraries
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

    /**
     * @param ContainerInterface $container
     *
     * @return Dedup
     */
    public static function getDedup(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config')->get('Index');
        $sesscontainer = new SessionContainer(
            'dedup',
            $container->get('VuFind\SessionManager')
        );
        $response = $container->get('Response');
        $cookie = $container->get('Request')->getCookie();
        return new Dedup($config, $sesscontainer, $response, $cookie);
    }
}
