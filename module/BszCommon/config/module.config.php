<?php

$config = [
    'service_manager' => [
        'factories' => [
            'BszCommon\Export' => 'VuFind\ExportFactory'
        ],
        'aliases' => [
            'VuFind\Export' => 'BszCommon\Export'
        ]
    ]
];
return $config;
