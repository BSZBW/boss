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

    public function __invoke(string $section = 'Items'): array
    {
        $config = $this->config->get($section);
        return $config == null ? [] : $this->readSection($config);
    }

    protected function readSection(Config $section): array
    {
        $ignore = array_merge(['disabled', 'type'], $this->getDisabled($section));

        $list = [];
        foreach ($section->toArray() ?? [] as $value => $key) {
            if (in_array($value, $ignore)) {
                continue;
            }

            $default = $this->getDefaultItem($value);
            $secConfig = $this->config->get($value);
            if ($secConfig == null) {
                $list[$key] = [$default];
                continue;
            }

            if(!array_key_exists($key, $list)) {
                $list[$key] = [];
            }
            $type = $secConfig->get('type', 'item');
            if($type == 'list') {
                $list[$key] = array_merge($list[$key], $this->readSection($secConfig));
            }elseif($type == 'item') {
                $list[$key][] =  $this->readItem($secConfig, $default);
            }
        }

        ksort($list);

        $retVal = [];
        foreach ($list as $items) {
            foreach ($items as $item) {
                $retVal[] = $item;
            }
        }
        return $retVal;
    }

    protected function readItem(Config $section, array $default): array
    {
        $template = $section->get('template', $default['template']);

        return [
            'label' => $section->get('label', $default['label']),
            'methods' => [
                'calls' => $this->getMethods($section, $default['methods']['calls'][0]['name']),
                'important' => $this->extractImportant($section)
            ],
            'class' => $this->getHtmlClass($section),
            'requirements' => $this->getRequirements($section),
            'template' => $template,
            'context' => $this->getContext2($section),
            'allowEmpty' => $this->getAllowEmpty($section)
        ];
    }

    protected function getDefaultItem(string $key): array
    {
        $defaultMethod = 'get' . $key;
        return [
            'label' => $key,
            'methods' => [
                'calls' => [
                    [
                        'name' => $defaultMethod,
                        'domain' => 'driver',
                        'args' => []
                    ]
                ],
                'important' => []
            ],
            'class' => '',
            'allowEmpty' => false,
            'requirements' => [],
            'template' => 'details/' . strtolower($key) . '.phtml',
            'context' => []
        ];
    }

    protected function getDisabled(Config $section): array
    {
        $disabled = $section->get('disabled', '');
        if(!is_array($disabled)){
            $disabled = [$disabled];
        }
        $retVal = [];
        foreach ($disabled as $item) {
            $retVal += array_filter(
                array_map('trim', explode(',', $item))
            );
        }
        return $retVal;
    }

    protected function getMethods(Config $section, string $defaultName): array
    {
        $methodNames = $section->get('method', $defaultName);
        if (is_string($methodNames)) {
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

    protected function getRequirements(Config $section)
    {
        $requirements = $section->get('requirement');
        if ($requirements == null) {
            $requirements = [];
        } elseif (is_string($requirements)) {
            $requirements = [$requirements];
        } else {
            $requirements = $requirements->toArray();
        }

        $retVal = [];
        foreach ($requirements as $req) {
            $parts = array_map('trim', explode('::', $req));
            if (count($parts) > 1) {
                $retVal[] = [
                    'domain' => $parts[0],
                    'name' => $parts[1]
                ];
            } else {
                $retVal[] = [
                    'domain' => 'driver',
                    'name' => $parts[0]
                ];
            }
        }
        return $retVal;
    }

    protected function extractMethodName(
        string $method,
        string $defaultDomain
    ): array {
        $parts = array_map('trim', explode("::", $method));
        if (count($parts) > 1) {
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
        if ($argString == null) {
            return $default;
        }

        return array_map('trim', explode(",", $argString));
    }

    protected function extractImportant(Config $section): array
    {
        $important = $section->get('important');
        if ($important !== null) {
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
                if (count($pair) !== 2) {
                    continue;
                }
                $key = trim($pair[0]);
                $value = $pair[1];

                if (!empty($key)) {
                    $retVal[$key] = $value;
                }
            }
            return $retVal;
        }
        return $contextVal->toArray();
    }

    protected function getAllowEmpty(Config $section): bool
    {
        return $section->get('allowEmpty') === 'true';
    }

    protected function getHtmlClass(Config $section)
    {
        return $section->get('class', '');
    }

}