<?php

namespace BszCommon\RecordDriver;

use Laminas\Config\Config;
use Psr\Container\ContainerInterface;

class RecordDetails
{

    protected $driver;
    protected ContainerInterface $container;
    protected Config $config;

    protected $lazyAuthors;

    public function __construct($driver, ContainerInterface $container)
    {
        $this->driver = $driver;
        $this->container = $container;

        $this->config = $this->container->get('VuFind\Config')->get('FullView');
    }

    protected function getAuthors(string $key)
    {
        if(!isset($this->lazyAuthors)) {
            $this->lazyAuthors= $this->driver->getDeduplicatedAuthors(
                ['role', 'gnd', 'live']
            );
        }
        return $this->lazyAuthors[$key];
    }

    public function getPrimaryAuthor()
    {
        return $this->getAuthors('primary');
    }

    public function getCorporateAuthor()
    {
        return $this->getAuthors('corporate');
    }
    public function get(string $method)
    {
        return is_callable([$this, $method]) ? $this->$method()
            : $this->driver->tryMethod($method);
    }

}