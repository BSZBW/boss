<?php

namespace BszTheme\View\Helper\Bodensee;

use Laminas\View\Helper\AbstractHelper;
use VuFind\RecordDriver\AbstractBase;

class RecordDetailsHelper extends AbstractHelper
{

    protected AbstractBase $driver;

    public function __invoke(AbstractBase $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    public function get(array $methods): mixed
    {
        $retVal = [];
        foreach ($methods['calls'] as $key => $value) {
            $retVal[$key] = $this->evaluate($value['name'], $value['domain'], $value['args'], true);
        }

        if(count($retVal) == 1 && array_key_exists(0, $retVal)) {
            return empty($retVal[0]) ? null: $retVal[0];
        }

        if(empty($methods['important'])) {
            foreach ($retVal as $value) {
                if(!empty($value)) {
                    return $retVal;
                }
            }
            return null;
        }

        foreach ($methods['important'] as $key) {
            if (empty($retVal[$key])) {
                return null;
            }
        }
        return $retVal;
    }

    public function checkRequirements(array $requirements): bool
    {
        foreach ($requirements as $req) {
            $methodName = $req['name'];
            $desired = true;
            if(str_starts_with($methodName, "!")) {
                $methodName = substr($methodName, 1);
                $desired = false;
            }

            if($this->evaluate($methodName, $req['domain']) !== $desired) {
                return false;
            }
        }
        //All test passed, return true
        return true;
    }

    protected function evaluate(string $methodName, string $domain, array $args = [], bool $useDriver = false): mixed
    {
        if($domain == 'driver') {
            return $this->driver->tryMethod($methodName, $args);
        }elseif($domain == 'flag') {
            $plugin = $this->getView()->plugin('client');
            return $plugin()->is($methodName);
        }
        $plugin = $this->getView()->plugin($domain);
        $plugin = $useDriver ? $plugin($this->driver) : $plugin;
        if(is_callable($plugin, $methodName)) {
            return $plugin->$methodName(...$args);
        }
        return null;
    }

}