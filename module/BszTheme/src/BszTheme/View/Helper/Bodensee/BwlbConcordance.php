<?php

namespace BszTheme\View\Helper\Bodensee;

use Laminas\View\Helper\AbstractHelper;

class BwlbConcordance extends AbstractHelper
{
    protected array $mapping;

    public function __construct(array $array)
    {
        $this->mapping = [];
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $this->mapping[$key] = $value;
            }
        }
    }

    public function __invoke(string $key): array
    {
        $retVal = [];
        for($i = 1; $i  <= strlen($key); $i++) {
            $subKey = substr($key, 0, $i);
            if (array_key_exists($subKey, $this->mapping)) {
                $retVal[$subKey] = $this->mapping[$subKey];
            }
        }
        return $retVal;
    }

}