<?php

namespace BszTheme\View\Helper\Bodensee;

use BszCommon\RecordDriver\RecordDetails;
use Laminas\View\Helper\AbstractHelper;
use Psr\Container\ContainerInterface;

class RecordDetailsHelper extends AbstractHelper
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($driver)
    {
        return new RecordDetails($driver, $this->container);
    }
}