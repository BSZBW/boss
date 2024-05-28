<?php

namespace BszTheme\View\Helper\Bodensee;

use Laminas\View\Helper\AbstractHelper;

class PluginChain extends AbstractHelper
{
    public function __invoke(string $data, string $pluginNames): string
    {
        $retVal = $data;
        $plugins = array_map('trim', explode(',', $pluginNames));
        foreach ($plugins as $pluginName) {
            $plugin = $this->getView()->plugin($pluginName);
            $retVal = $plugin($retVal);
        }
        return $retVal;
    }

}