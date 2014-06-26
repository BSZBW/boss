<?php
return array(
  'extends' => 'bootstrap3',

  'css'	=> array(
    'sbvfrd.css',
  ),

  'helpers' => array(
     'factories' => array(
         'record'                        => 'Swissbib\View\Helper\Swissbib\Factory::getRecordHelper',
         'flashmessages'                 => 'Swissbib\View\Helper\Swissbib\Factory::getFlashMessages',
         'citation'                      => 'Swissbib\View\Helper\Swissbib\Factory::getCitation',
         'recordlink'                    => 'Swissbib\View\Helper\Swissbib\Factory::getRecordLink',
         'getextendedlastsearchlink'     => 'Swissbib\View\Helper\Swissbib\Factory::getExtendedLastSearchLink'
     )
   )
);