<?php

namespace BszTheme\View\Helper\Bodensee;

use Bsz\Config\Client;
use Laminas\View\Helper\AbstractHelper;
use VuFind\View\Helper\Root\Context;

class MainzMapongo extends AbstractHelper
{

    protected Client $config;

    protected Context $context;

    public function __construct(Client $config, Context $context)
    {
        $this->config = $config;
        $this->context = $context;
    }

    public function __invoke(String $signature, String $location)
    {
        $baseUrl = $this->config->get('baseUrl','');
        $params = [urlencode($signature), urlencode($location)];
        $url = str_replace(['%SIG%', '%LOC%'], $params, $baseUrl);
        return $this->getView()->render(
            'Helpers/mainzmapongo.phtml', ['url' => $url]
        );
    }


}