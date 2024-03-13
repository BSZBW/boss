<?php

namespace BszTheme\View\Helper\Bodensee;

use BszCommon\RecordDriver\RecordDetails;
use Laminas\Config\Config;
use Laminas\View\Helper\AbstractHelper;

class RecordDetailsHelper extends AbstractHelper
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function __invoke($driver)
    {
        return new RecordDetails($driver, $this->config);
    }
}