<?php
return array(
    'extends' => 'root',
    'css' => array(
        'bootstrap.min.css',
        'font-awesome.min.css',
        'bootstrap-accessibility.css',
        'bootstrap-custom.css',
        'print.css:print',
        'slider.css',
    ),
    'js' => array(
        'core/jquery.min.js',
        'core/bootstrap.min.js',
        'core/bootstrap-accessibility.min.js',
        'core/typeahead.js',
        'common.js',
        'lightbox.js',
        'rc4.js'
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
