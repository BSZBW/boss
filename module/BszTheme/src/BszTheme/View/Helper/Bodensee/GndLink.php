<?php

namespace BszTheme\View\Helper\Bodensee;

use Bsz\Tools\GndHelperTrait;
use Laminas\View\Helper\AbstractHelper;

class GndLink extends AbstractHelper
{
    use GndHelperTrait;

    public function __invoke()
    {
        return $this;
    }

    public function fromDnb(string $id): string
    {
        return $this->gndLinkFromId($id);
    }

    public function fromExplore(string $id): string
    {
        return $this->exploreLinkFromId($id);
    }

}
