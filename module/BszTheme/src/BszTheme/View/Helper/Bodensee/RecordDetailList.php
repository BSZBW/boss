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

//    public function __invoke()
//    {
//        $retVal = [];
//        foreach ($this->config->Items ?? [] as $key => $value) {
//
//            $defaultMethod = 'get' . $key;
//            $method = $this->config->Methods->get($key, $defaultMethod);
//
//            $defaultTemplate = 'details/' . strtolower($key) . '.phtml';
//            $template = $this->config->Templates->get($key, $defaultTemplate);
//
//            $retVal[] = [
//                'label' => $value,
//                'method' => $method,
//                'template' => $template,
//                'context' => $this->getContext($key, [])
//            ];
//        }
//        return $retVal;
//    }

    public function __invoke(string $section = 'Items'): array
    {
        $retVal = [];
        $list = $this->config->get($section)->toArray();
        foreach ($list ?? [] as $key => $value) {

            $defaultMethod = 'get' . $key;
            $default = [
                'label' => $value,
                'methods' => [
                    'calls' => [[
                        'name' => $defaultMethod,
                        'domain' => 'driver',
                        'args' => []
                    ]],
                    'important' => []
                ],
                'allowEmpty' => false,
                'requirements' => [],
                'template' => 'details/' . strtolower($key) . '.phtml',
                'context' => []
            ];

            $section = $this->config->get($key);
            if($section == null) {
                $retVal[] = $default;
                continue;
            }
            $template = $section->get('template', $default['template']);

            $retVal[] = [
                'label' => $value,
                'methods' => [
                    'calls' => $this->getMethods($section, $defaultMethod),
                    'important' => $this->extractImportant($section)
                ],
                'requirements' => $this->getRequirements($section),
                'template' => $template,
                'context' => $this->getContext2($section),
                'allowEmpty' => $this->getAllowEmpty($section)
            ];
        }
        return $retVal;
    }

    protected function getMethods(Config $section, string $defaultName): array
    {
        $methodNames =  $section->get('method', $defaultName);
        if(is_string($methodNames)) {
            $retVal = $this->extractMethodName($methodNames, 'driver');
            $retVal['args'] = $this->extractArguments($section, []);
            return [$retVal];
        }

        $retVal = [];
        foreach ($methodNames as $key => $value) {
            $arr = $this->extractMethodName($value, 'driver');
            $arr['args'] = $this->extractArguments($section, []);
            $retVal[$key] = $arr;
        }
        return $retVal;
    }

    protected function getRequirements(Config $section) {
        $requirements = $section->get('requirement');
        if($requirements == null) {
            $requirements = [];
        }elseif (is_string($requirements)) {
            $requirements = [$requirements];
        } else {
            $requirements = $requirements->toArray();
        }

        $retVal = [];
        foreach($requirements as $req) {
            $parts = array_map('trim', explode('::', $req));
            if (count($parts) > 1) {
                $retVal[] = [
                    'domain' => $parts[0],
                    'name' => $parts[1]
                ];
            }else {
                $retVal[] = [
                    'domain' => 'driver',
                    'name' => $parts[0]
                ];
            }
        }
        return $retVal;
    }

    protected function extractMethodName(string $method, string $defaultDomain): array
    {
        $parts = array_map('trim', explode("::", $method));
        if(count($parts) > 1) {
            return [
                'name' => $parts[1],
                'domain' => $parts[0]
            ];
        }
        return [
            'name' => $parts[0],
            'domain' => $defaultDomain
        ];
    }

    protected function extractArguments(Config $section, array $default): array
    {
        $argString = $section->get('arguments');
        if($argString == null) {
            return $default;
        }

        return array_map('trim', explode(",", $argString));
    }

    protected function extractImportant(Config $section): array
    {
        $important = $section->get('important');
        if($important !== null) {
            return array_filter(
                array_map('trim', explode(",", $important))
            );
        }
        return [];
    }

    protected function getContext2(Config $section): array
    {
        $contextVal = $section->get('context', '');
        if (is_string($contextVal)) {
            $retVal = [];
            $parts = explode(",", $contextVal);
            foreach ($parts as $p) {
                $pair = explode(":", $p);
                if(count($pair) !== 2) {
                    continue;
                }
                $key = trim($pair[0]);
                $value = $pair[1];

                if(!empty($key)) {
                    $retVal[$key] = $value;
                }
            }
            return $retVal;
        }
        return $contextVal->toArray();
    }

    protected function getContext(string $key, array $default): array
    {
        $retVal = [];

        $section = $this->config->get('Context');
        if($section == null) {
            return $default;
        }

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

    protected function getAllowEmpty(Config $section): bool
    {
        return $section->get('allowEmpty') === 'true';
    }

}