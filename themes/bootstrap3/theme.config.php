<?php
return array(
    'extends' => 'root',
    'css' => array(
        'a11y.css',
        //'bootstrap.min.css',
        //'font-awesome.min.css',
        //'bootstrap-accessibility.css',
        //'bootstrap-custom.css',
        'print.css:print',
        'slider.css',
    ),
    'js' => array(
        'vendor/jquery.min.js',
        'vendor/bootstrap.min.js',
        'vendor/bootstrap-accessibility.min.js',
        'vendor/typeahead.js',
        'common.js',
        'lightbox.js',
        'rc4.js',
        //'vendor/cssrefresh.js'
    ),
    'less' => array(
        'bootstrap-core.less',
        'font-awesome.less'
    ),
    'scss' => array(
        //'core.scss',
        //'font-awesome.scss'
    ),
    'favicon' => 'vufind-favicon.ico',
    'helpers' => array(
        'factories' => array(
            'flashmessages' => 'VuFind\View\Helper\Bootstrap3\Factory::getFlashmessages',
            'layoutclass' => 'VuFind\View\Helper\Bootstrap3\Factory::getLayoutClass',
        ),
        'invokables' => array(
            'highlight' => 'VuFind\View\Helper\Bootstrap3\Highlight',
            'search' => 'VuFind\View\Helper\Bootstrap3\Search',
            'vudl' => 'VuDL\View\Helper\Bootstrap3\VuDL',
        )
    )
);
