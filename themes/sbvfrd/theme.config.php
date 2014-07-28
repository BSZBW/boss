<?php
return array(
    'extends' => 'bootstrap3',

    'css' => array(
        'sbvfrd.css',
        'sbvfrd_xs.css',
        'sbvfrd_sm.css',
        'sbvfrd_md.css',
        'sbvfrd_lg.css',
        'search_home.css'
    ),

    'js' => array(
        'jquery/ui/jquery-ui.min.js',
//
        'lib/jstorage.min.js', //used for favorites - there is still some amount of JS code inline of the page -> Todo: Refactoring in upcoming Sprints

        'jquery/plugin/jquery-migrate-1.2.1.js',
        'jquery/plugin/jquery.easing.js',
        'jquery/plugin/jquery.debug.js',
        'jquery/plugin/colorbox/jquery.colorbox.js', //popup dialog solution
        'jquery/plugin/jquery.cookie.js',
        'jquery/plugin/jquery.spritely.js', // sprite animation, e.g. for ajax spinner
        'jquery/plugin/jquery.validate.min.js',
        'jquery/plugin/jquery.hoverintent.js',
        'jquery/plugin/loadmask/jquery.loadmask.js',
        'jquery/plugin/jquery.form.min.js',

        'swissbib-jq-plugins/hint.js',
        'swissbib-jq-plugins/menunav.js',
        'swissbib-jq-plugins/info.js',
        'swissbib-jq-plugins/info.rollover.js',
        'swissbib-jq-plugins/toggler.js',
        'swissbib-jq-plugins/checker.js',
        'swissbib-jq-plugins/dropdown.js',
        'swissbib-jq-plugins/tabbed.js',
        'swissbib-jq-plugins/enhancedsearch.js',
        'swissbib-jq-plugins/extended.ui.autocomplete.js',
        '../themes/bootstrap3/js/vendor/jsTree/jstree.min.js',

        'swissbib/swissbib.js',

        'swissbib/AdvancedSearch.js',
        'swissbib/Holdings.js',
        'swissbib/HoldingFavorites.js',
        'swissbib/FavoriteInstitutions.js',
        'swissbib/Account.js',
        'swissbib/Settings.js',

        'blueprint/lightbox.js',
        'blueprint/bulk_actions.js',
    ),

    'helpers' => array(
        'factories' => array(
            'record' => 'Swissbib\View\Helper\Swissbib\Factory::getRecordHelper',
            'flashmessages' => 'Swissbib\View\Helper\Swissbib\Factory::getFlashMessages',
            'citation' => 'Swissbib\View\Helper\Swissbib\Factory::getCitation',
            'recordlink' => 'Swissbib\View\Helper\Swissbib\Factory::getRecordLink',
            'getextendedlastsearchlink' => 'Swissbib\View\Helper\Swissbib\Factory::getExtendedLastSearchLink'
        )
    )
);