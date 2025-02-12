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

    protected $area;

    public function __construct($config, $context)
    {
        $this->hanConfig = $config;
        $this->context = $context;
    }

    public function __invoke($driver, $autoLoad, $area): HanApi
    {
        $this->driver = $driver;
        $this->autoload = $autoLoad;
        $this->area = $area;
        return $this;
    }

    public function renderTemplate()
    {
        $alternative = $this->getAlternative();
        return $this->context->__invoke($this->getView())->renderInContext(
            'Helpers/hanembed.phtml',
            [
                'autoloadClass' => $this->autoload,
                'data' => $this->getParams(),
                'alternative' => $alternative
            ]
        );
    }

    public function isActive(): bool
    {
        if($this->driver == null || $this->hanConfig == null) {
            return false;
        }

        $showif = $this->hanConfig->get('showif');
        if(!isset($showif)) {
            return true;
        }
        if(is_string($showif)) {
            $showif = [$showif];
        }else {
            $showif = $showif->toArray();
        }
        if(empty($showif)) {
            return true;
        }
        foreach ($showif as $item) {
            $parts = explode(':', $item);
            $fun = $parts[0];
            $args = (count($parts) > 1) ? explode(',', $parts[1]) : [];
            if ($this->driver->tryMethod($fun, $args)) {
                return true;
            }
        }
        return false;

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

    protected function getAlternative()
    {
        if ($this->area == 'results') {
            return '';
        }
        $freeUrl = $this->driver->tryMethod('getFreeURLs');
        return !empty($freeUrl)
            ? 'Siehe "Kostenloser Online-Zugang"'
            : 'no_link_available' ;
    }

}