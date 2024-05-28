<?php

namespace BszTheme\View\Helper\Bodensee;

use Laminas\View\Helper\AbstractHelper;
use VuFind\RecordDriver\AbstractBase;

class BoolHelper extends AbstractHelper
{

    public function __invoke()
    {
        return $this;
    }

    public function userTagsEnabled(): bool
    {
        $plugin = $this->getView()->plugin('usertags');
        return $plugin->getMode() !== 'disabled';
    }

}