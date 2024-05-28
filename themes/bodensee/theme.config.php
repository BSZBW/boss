<?php
/*
 * Copyright 2020 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$config = [
    'extends' => 'bootstrap3',
    'favicon' => '{{client_favicon}}',
    'css' => [
        '{{client_stylesheet}}'
    ],
    'js' => [
        'additions.js',
        'vendor/clipboard.min.js'
    ],
    'helpers' => [
        'factories' => [
            'Bsz\View\Helper\Bootstrap3\LayoutClass' => 'BszTheme\View\Helper\Bodensee\Factory::getLayoutClass',
            'Bsz\View\Helper\Root\OpenUrl' => 'BszTheme\View\Helper\Bodensee\Factory::getOpenUrl',
            'Bsz\View\Helper\Root\Record' => 'BszTheme\View\Helper\Bodensee\Factory::getRecord',
            'Bsz\View\Helper\Root\RecordLink' => 'BszTheme\View\Helper\Bodensee\Factory::getRecordLink',
            'Bsz\View\Helper\Root\Piwik' => 'BszTheme\View\Helper\Bodensee\Factory::getPiwik',
            'Bsz\View\Helper\Root\SearchTabs' => 'BszTheme\View\Helper\Bodensee\Factory::getSearchTabs',
            'Bsz\View\Helper\Root\SearchMemory' => 'BszTheme\View\Helper\Bodensee\Factory::getSearchMemory',
            'illform' => 'BszTheme\View\Helper\Bodensee\Factory::getIllForm',
            'BszTheme\View\Helper\Bodensee\Wayfless' => 'BszTheme\View\Helper\Bodensee\Factory::getWayfless',
            'BszTheme\View\Helper\Bodensee\BwlbConcordance' =>  \Laminas\ServiceManager\Factory\InvokableFactory::class,
            'BszTheme\View\Helper\Bodensee\GndLink' => \Laminas\ServiceManager\Factory\InvokableFactory::class,
            'BszTheme\View\Helper\Bodensee\IdVerifier' => 'BszTheme\View\Helper\Bodensee\Factory::getIdVerifier'
        ],
        'aliases' => [
            'VuFind\View\Helper\Bootstrap3\LayoutClass' => 'Bsz\View\Helper\Bootstrap3\LayoutClass',
            'VuFind\View\Helper\Root\OpenUrl' => 'Bsz\View\Helper\Root\OpenUrl',
            'VuFind\View\Helper\Root\Record' => 'Bsz\View\Helper\Root\Record',
            'VuFind\View\Helper\Root\RecordLinker' => 'Bsz\View\Helper\Root\RecordLink',
            'VuFind\View\Helper\Root\Piwik' => 'Bsz\View\Helper\Root\Piwik',
            'VuFind\View\Helper\Root\SearchTabs' => 'Bsz\View\Helper\Root\SearchTabs',
            'VuFind\View\Helper\Root\SearchMemory' => 'Bsz\View\Helper\Root\SearchMemory',
            'wayfless' => 'BszTheme\View\Helper\Bodensee\Wayfless',
            'gndLink' => 'BszTheme\View\Helper\Bodensee\GndLink',
            'recordLink' => 'VuFind\View\Helper\Root\RecordLinker',
            'bwlbConcordance' => 'BszTheme\View\Helper\Bodensee\BwlbConcordance',
            'idVerifier' => 'BszTheme\View\Helper\Bodensee\IdVerifier'
        ]
    ],
    'icons' => [
        'defaultSet' => 'FontAwesome',
        'sets' => [
            /**
             * Define icon sets here.
             *
             * All sets need:
             * - 'template': which template the icon renders with
             * - 'src': the location of the relevant resource (font, css, images)
             * - 'prefix': prefix to place before each icon name for convenience
             *             (ie. fa fa- for FontAwesome, default "")
             */
            'FontAwesome' => [
                // Specifically Font Awesome 4.7
                'template' => 'font',
                'prefix' => 'fa fa-',
                // Right now, FontAwesome is bundled into compiled.css; when we no
                // longer globally rely on FA (by managing all icons through the
                // helper), we should change this to 'vendor/font-awesome.min.css'
                // so it only loads conditionally when icons are used.
                'src' => 'vendor/font-awesome.min.css',
            ],
            'Collapse' => [
                'template' => 'collapse',
            ],
            // Unicode symbol characters. Icons are defined as hex code points.
            'Unicode' => [
                'template' => 'unicode',
            ],
            /* For an example of an images set, see Bootprint's theme.config.php. */
        ],
        'aliases' => [
            /**
             * Icons can be assigned or overriden here
             *
             * Format: 'icon' => [set:]icon[:extra_classes]
             * Icons assigned without set will use the defaultSet.
             * In order to specify extra CSS classes, you must also specify a set.
             *
             * All of the items below have been specified with FontAwesome to allow
             * for a strong inheritance safety net but this is not required.
             */
            'addthis-bookmark' => 'FontAwesome:bookmark-o',
            'barcode' => 'FontAwesome:barcode',
            'browzine-issue' => 'Alias:format-serial',
            'browzine-pdf' => 'FontAwesome:file-pdf-o',
            'browzine-retraction' => 'FontAwesome:exclamation',
            'cart' => 'FontAwesome:suitcase',
            'cart-add' => 'FontAwesome:plus',
            'cart-empty' => 'FontAwesome:times',
            'cart-remove' => 'FontAwesome:minus-circle',
            'cite' => 'FontAwesome:asterisk',
            'collapse' => 'Collapse:_', // uses the icons below
            'collapse-close' => 'FontAwesome:chevron-up',
            'collapse-open' => 'FontAwesome:chevron-down',
            'cover-replacement' => 'FontAwesome:question',
            'currency-eur' => 'FontAwesome:eur',
            'currency-gbp' => 'FontAwesome:gbp',
            'currency-inr' => 'FontAwesome:inr',
            'currency-jpy' => 'FontAwesome:jpy',
            'currency-krw' => 'FontAwesome:krw',
            'currency-rmb' => 'FontAwesome:rmb',
            'currency-rub' => 'FontAwesome:rub',
            'currency-try' => 'FontAwesome:try',
            'currency-usd' => 'FontAwesome:usd',
            'currency-won' => 'FontAwesome:won',
            'currency-yen' => 'FontAwesome:yen',
            'dropdown-caret' => 'FontAwesome:caret-down',
            'export' => 'FontAwesome:external-link',
            'external-link' => 'FontAwesome:link',
            'facet-applied' => 'FontAwesome:check',
            'facet-checked' => 'FontAwesome:check-square-o',
            'facet-collapse' => 'Unicode:25BD',
            'facet-exclude' => 'FontAwesome:times',
            'facet-expand' => 'Unicode:25B6',
            'facet-noncollapsible' => 'FontAwesome:none',
            'facet-unchecked' => 'FontAwesome:square-o',
            'feedback' => 'FontAwesome:envelope',
            'format-atlas' => 'FontAwesome:compass',
            'format-book' => 'FontAwesome:book',
            'format-braille' => 'FontAwesome:hand-o-up',
            'format-cdrom' => 'FontAwesome:laptop',
            'format-chart' => 'FontAwesome:signal',
            'format-chipcartridge' => 'FontAwesome:laptop',
            'format-collage' => 'FontAwesome:picture-o',
            'format-default' => 'FontAwesome:book',
            'format-disccartridge' => 'FontAwesome:laptop',
            'format-drawing' => 'FontAwesome:picture-o',
            'format-ebook' => 'FontAwesome:file-text-o',
            'format-electronic' => 'FontAwesome:file-archive-o',
            'format-file' => 'FontAwesome:file-o',
            'format-filmstrip' => 'FontAwesome:film',
            'format-flashcard' => 'FontAwesome:bolt',
            'format-floppydisk' => 'FontAwesome:save',
            'format-folder' => 'FontAwesome:folder',
            'format-globe' => 'FontAwesome:globe',
            'format-journal' => 'FontAwesome:file-text-o',
            'format-kit' => 'FontAwesome:briefcase',
            'format-manuscript' => 'FontAwesome:file-text-o',
            'format-map' => 'FontAwesome:compass',
            'format-microfilm' => 'FontAwesome:film',
            'format-motionpicture' => 'FontAwesome:video-camera',
            'format-musicalscore' => 'FontAwesome:music',
            'format-musicrecording' => 'FontAwesome:music',
            'format-newspaper' => 'FontAwesome:file-text-o',
            'format-online' => 'FontAwesome:laptop',
            'format-painting' => 'FontAwesome:picture-o',
            'format-photo' => 'FontAwesome:picture-o',
            'format-photonegative' => 'FontAwesome:picture-o',
            'format-physicalobject' => 'FontAwesome:archive',
            'format-print' => 'FontAwesome:picture-o',
            'format-sensorimage' => 'FontAwesome:picture-o',
            'format-serial' => 'FontAwesome:file-text-o',
            'format-slide' => 'FontAwesome:film',
            'format-software' => 'FontAwesome:laptop',
            'format-soundcassette' => 'FontAwesome:headphones',
            'format-sounddisc' => 'FontAwesome:laptop',
            'format-soundrecording' => 'FontAwesome:headphones',
            'format-tapecartridge' => 'FontAwesome:laptop',
            'format-tapecassette' => 'FontAwesome:headphones',
            'format-tapereel' => 'FontAwesome:film',
            'format-transparency' => 'FontAwesome:film',
            'format-unknown' => 'FontAwesome:question',
            'format-video' => 'FontAwesome:video-camera',
            'format-videocartridge' => 'FontAwesome:video-camera',
            'format-videocassette' => 'FontAwesome:video-camera',
            'format-videodisc' => 'FontAwesome:laptop',
            'format-videoreel' => 'FontAwesome:video-camera',
            'hierarchy-tree' => 'FontAwesome:sitemap',
            'lightbox-close' => 'FontAwesome:times',
            'more' => 'FontAwesome:chevron-circle-right',
            'more-rtl' => 'FontAwesome:chevron-circle-left',
            'my-account' => 'FontAwesome:user-circle-o',
            'my-account-notification' => 'Alias:notification',
            'my-account-warning' => 'Alias:warning',
            'notification' => 'FontAwesome:bell',
            'options' => 'FontAwesome:gear',
            'overdrive' => 'FontAwesome:download',
            'overdrive-cancel-hold' => 'FontAwesome:flag-o',
            'overdrive-checkout' => 'FontAwesome:arrow-left',
            'overdrive-checkout-rtl' => 'FontAwesome:arrow-right',
            'overdrive-download' => 'FontAwesome:download',
            'overdrive-help' => 'FontAwesome:question-circle',
            'overdrive-place-hold' => 'FontAwesome:flag-o',
            'overdrive-return' => 'FontAwesome:arrow-right',
            'overdrive-return-rtl' => 'FontAwesome:arrow-left',
            'overdrive-sign-in' => 'FontAwesome:sign-in',
            'overdrive-success' => 'FontAwesome:check',
            'overdrive-warning' => 'Alias:warning',
            'page-first' => 'FontAwesome:angle-double-left',
            'page-first-rtl' => 'FontAwesome:angle-double-right',
            'page-last' => 'FontAwesome:angle-double-right',
            'page-last-rtl' => 'FontAwesome:angle-double-left',
            'page-next' => 'FontAwesome:angle-right',
            'page-next-rtl' => 'FontAwesome:angle-left',
            'page-prev' => 'FontAwesome:angle-left',
            'page-prev-rtl' => 'FontAwesome:angle-right',
            'place-hold' => 'FontAwesome:flag',
            'place-ill-request' => 'FontAwesome:exchange',
            'place-recall' => 'FontAwesome:flag',
            'place-storage-retrieval' => 'FontAwesome:truck',
            'print' => 'FontAwesome:print',
            'profile' => 'FontAwesome:user',
            'profile-card-delete' => 'Alias:ui-delete',
            'profile-card-edit' => 'Alias:ui-edit',
            'profile-change-password' => 'FontAwesome:key',
            'profile-delete' => 'Alias:ui-delete',
            'profile-edit' => 'Alias:ui-edit',
            'profile-email' => 'FontAwesome:envelope',
            'profile-sms' => 'FontAwesome:phone',
            'qrcode' => 'FontAwesome:qrcode',
            'rating-half' => 'FontAwesome:star-half',
            'rating-full' => 'FontAwesome:star',
            'search' => 'FontAwesome:search',
            'search-delete' => 'Alias:ui-delete',
            'search-filter-remove' => 'FontAwesome:times',
            'search-rss' => 'FontAwesome:rss',
            'search-save' => 'Alias:ui-save',
            'search-schedule-alert' => 'FontAwesome:exclamation-circle',
            'send-email' => 'FontAwesome:envelope',
            'send-sms' => 'FontAwesome:phone',
            'sign-in' => 'FontAwesome:sign-in',
            'sign-out' => 'FontAwesome:sign-out',
            'spinner' => 'FontAwesome:spinner:icon--spin',
            'status-available' => 'FontAwesome:check',
            'status-pending' => 'FontAwesome:clock-o',
            'status-ready' => 'FontAwesome:bell',
            'status-unavailable' => 'FontAwesome:times',
            'status-unknown' => 'FontAwesome:circle',
            'tag-add' => 'Alias:ui-add',
            'tag-remove' => 'Alias:ui-remove',
            'tree-context' => 'FontAwesome:sitemap',
            'truncate-less' => 'FontAwesome:arrow-up',
            'truncate-more' => 'FontAwesome:arrow-down',
            'ui-add' => 'FontAwesome:plus-circle',
            'ui-cancel' => 'FontAwesome:ban',
            'ui-close' => 'FontAwesome:times',
            'ui-delete' => 'FontAwesome:trash-o',
            'ui-dots-menu' => 'FontAwesome:ellipsis-h',
            'ui-edit' => 'FontAwesome:edit',
            'ui-failure' => 'FontAwesome:times',
            'ui-menu' => 'FontAwesome:bars',
            'ui-remove' => 'FontAwesome:times',
            'ui-reset-search' => 'Alias:ui-remove',
            'ui-save' => 'FontAwesome:floppy-o',
            'ui-success' => 'FontAwesome:check',
            'user-checked-out' => 'FontAwesome:book',
            'user-favorites' => 'FontAwesome:star',
            'user-holds' => 'FontAwesome:flag',
            'user-ill-requests' => 'FontAwesome:exchange',
            'user-list' => 'FontAwesome:list',
            'user-list-add' => 'FontAwesome:bookmark-o',
            'user-list-delete' => 'Alias:ui-delete',
            'user-list-edit' => 'Alias:ui-edit',
            'user-list-entry-edit' => 'Alias:ui-edit',
            'user-list-remove' => 'Alias:ui-remove',
            'user-loan-history' => 'FontAwesome:history',
            'user-public-list-indicator' => 'FontAwesome:globe',
            'user-storage-retrievals' => 'FontAwesome:archive',
            'view-grid' => 'FontAwesome:th',
            'view-list' => 'FontAwesome:list',
            'view-visual' => 'FontAwesome:th-large',
            'warning' => 'FontAwesome:exclamation-triangle',
        ],
    ],
];
return $config;
