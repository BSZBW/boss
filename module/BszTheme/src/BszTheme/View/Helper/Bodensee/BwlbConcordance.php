<?php

namespace BszTheme\View\Helper\Bodensee;

use Laminas\View\Helper\AbstractHelper;

class BwlbConcordance extends AbstractHelper
{

    public function __invoke(string $key): array
    {
        $transEsc = $this->getView()->plugin('transEsc');

        $retVal = [];
        for($i = 1; $i  <= strlen($key); $i++) {
            $subKey = substr($key, 0, $i);
            $str = $transEsc('BWLB::' . $subKey);
            if ($str !== $subKey) {
                $retVal[$subKey] = $str;
            }
        }
        return $retVal;
    }

}