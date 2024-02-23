<?php

namespace BszTheme\View\Helper\Bodensee;

use Laminas\Config\Config;
use Laminas\View\Helper\AbstractHelper;

class BibliographyIcon extends AbstractHelper
{
    protected array $icons;

    public function __construct(Config $config)
    {
        $this->icons = [];
        $data = $config->get('Icons');
        foreach ($data as $k => $item) {
            if(!is_string($item)) {
                continue;
            }
            $iconInfo = explode('#', $item);
            $tmp = ['imgFile' => $iconInfo[0]];
            if (count($iconInfo) > 1) {
                $tmp['url'] = $iconInfo[1];
            }
            $this->icons[strtolower($k)] = $tmp;
        }
    }

    public function __invoke(string $iconName): string
    {
        $icon = $this->icons[strtolower($iconName)] ?? [];
        if (!empty($icon)) {
            return $this->getView()->render(
                'Helpers/bibliographyIcon.phtml',
                $icon + ['alt' => $iconName]
            );
        }
        return '';
    }
}