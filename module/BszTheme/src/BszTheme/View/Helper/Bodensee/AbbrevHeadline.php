<?php

namespace BszTheme\View\Helper\Bodensee;

use Laminas\View\Helper\AbstractHelper;

class AbbrevHeadline extends AbstractHelper
{
    protected $abbrevList;

    public function __construct()
    {
        $this->abbrevList = [
            'ISBN' => 'ISBN_long',
            'ISSN' => 'ISSN_long',
            'DFI' => 'DFI',
            'RVK' => 'RVK',
            'FIV' => 'FIV',
            'STW' => 'STW',
            'GND' => 'GND',
            'PPN' => 'PPN',
            'GTIN' => 'GTIN'
        ];
    }

    public function __invoke(string $headline): string
    {
        $parts = explode('-', $headline);

        $list = [];
        foreach ($parts as $part) {
            $arr = ['abbrev' => $part];
            if(array_key_exists($part, $this->abbrevList)) {
                $arr['description'] = $this->abbrevList[$part];
            }
            $list[] = $arr;
        }
        return $this->getView()->render(
            'Helpers/abbrevhead.phtml',
            ['data' => $list]
        );
    }

}