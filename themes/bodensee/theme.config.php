<?php
$config = [
    'extends' => 'bootstrap3',
      'favicon' => '/themes/bodensee/images/favicon/default.ico',
    'js' => [
        'additions.js'
    ],     
    'helpers' => [
        'factories' => [
            'layoutclass' => 'BszTheme\View\Helper\Bodensee\Factory::getLayoutClass',
            'openurl' => 'BszTheme\View\Helper\Bodensee\Factory::getOpenUrl',
            'record' => 'BszTheme\View\Helper\Bodensee\Factory::getRecord',
            'recordLink' => 'BszTheme\View\Helper\Bodensee\Factory::getRecordLink',
            'getLastSearchLink' => 'BszTheme\View\Helper\Bodensee\Factory::getGetLastSearchLink',
            'illform' => 'BszTheme\View\Helper\Factory::getIllForm',
            'piwik' => 'BszTheme\View\Helper\Bodensee\Factory::getPiwik',
            // this factory in Bodensee does not yet work so I've linked it to Vufind
            'searchTabs' => 'VuFind\View\Helper\Root\Factory::getSearchTabs',
        ],
    ]
];
return $config;
