<?php

namespace BszCommon\RecordDriver;

class RecordDetails
{

    protected $driver;
    protected $config;

    public function __construct($driver, $config)
    {
        $this->driver = $driver;
        $this->config = $config;
    }

    public function get(string $method)
    {
        return is_callable([$this, $method]) ? $this->$method()
            : $this->driver->tryMethod($method);
    }

}