<?php

namespace BszTheme\View\Helper\Bodensee;

use Laminas\Config\Config;
use Laminas\View\Helper\AbstractHelper;
use VuFind\View\Helper\Root\Context;

class HanApi extends AbstractHelper
{
    protected $hanConfig;

    protected Context $context;

    protected $autoload;

    protected $driver;

    public function __construct($config, $context)
    {
        $this->hanConfig = $config;
        $this->context = $context;
    }

    public function __invoke($driver, $autoLoad): HanApi
    {
        $this->driver = $driver;
        $this->autoload = $autoLoad;
        return $this;
    }

    public function renderTemplate()
    {
        return $this->context->__invoke($this->getView())->renderInContext(
            'Helpers/hanembed.phtml',
            [
                'autoloadClass' => $this->autoload,
                'data' => $this->getParams()
            ]
        );
    }

    public function isActive(): bool
    {
        if($this->driver == null || $this->hanConfig == null) {
            return false;
        }

        return true;

//        $isArticle = $this->driver->tryMethod('isArticle');
//        $isElectronic = $this->driver->tryMethod('isElectronic');
//
//        return $isArticle && $isElectronic;
    }

    protected function getParams()
    {
        if ($this->driver == null) {
            return [];
        }

        $params = array_filter([
            'method' => 'getHANID',
            'id' => $this->hanConfig->get('id'),
            'return' => '1',
            'title' => $this->driver->tryMethod('getContainerTitle'),
            'eissn' => $this->driver->tryMethod('getCleanIssn'),
            'doi' => $this->driver->tryMethod('getCleanDoi'),
            'url' => $this->driver->tryMethod('getFirstUrl', ['Resolving-Dienst'])
        ]);

        return json_encode($params);
    }

}