<?php

namespace BszTheme\View\Helper\Bodensee;

use Bsz\Tools\GndHelperTrait;
use Laminas\View\Helper\AbstractHelper;

class GndLink extends AbstractHelper
{
    use GndHelperTrait;

    public function __invoke(String $gndId)
    {
        return $this->gndLinkFromId($gndId);
    }

}
