<?php

namespace BszCommon\Search;

use BszCommon\Search\Factory\AbstractSearchNBackendFactory;

class BackendRegistry extends \VuFind\Search\BackendRegistry
{
    public function __construct($configOrContainerInstance = null, array $v3config = [])
    {
        $this->addAbstractFactory(AbstractSearchNBackendFactory::class);
        parent::__construct($configOrContainerInstance, $v3config);
    }

}