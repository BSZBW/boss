<?php

namespace BszCommon\Search;

use Interop\Container\ContainerInterface;

class BackendManagerFactory extends \VuFind\Search\BackendManagerFactory
{

    protected function getRegistry(ContainerInterface $container)
    {
        $config = $container->get('config');
        return new BackendRegistry(
            $container, $config['vufind']['plugin_managers']['search_backend']
        );
    }

}