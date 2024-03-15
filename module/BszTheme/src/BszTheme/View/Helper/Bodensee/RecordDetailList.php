<?php

namespace BszTheme\View\Helper\Bodensee;

use Laminas\Config\Config;
use Laminas\View\Helper\AbstractHelper;

class RecordDetailList extends AbstractHelper
{
    protected Config $config;

    public function __construct(Config $fullViewConfig)
    {
        $this->config = $fullViewConfig;
    }

    public function __invoke()
    {
        $retVal = [];
        foreach ($this->config->Items as $key => $value) {

            $defaultMethod = 'get' . $key;
            $method = $this->config->Methods->get($key, $defaultMethod);

            $defaultTemplate = 'details/' . strtolower($key) . '.phtml';
            $template = $this->config->Templates->get($key, $defaultTemplate);

            $retVal[] = [
                'label' => $value,
                'method' => $method,
                'template' => $template,
                'context' => $this->getContext($key)
            ];
        }
        return $retVal;
    }

    protected function getContext(string $key): array
    {
        $retVal = [];

        $valueString = $this->config->Context->get($key, '');
        $parts = explode(",", $valueString);
        foreach ($parts as $p) {
            $pair = explode(":", $p);
            if(count($pair) !== 2) {
                continue;
            }
            $key = trim($pair[0]);
            $value = trim($pair[1]);

            if(!empty($key)) {
                $retVal[$key] = $value;
            }
        }

        return $retVal;
    }

}