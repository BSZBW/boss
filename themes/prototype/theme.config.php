<?php
return array(
	'extends' => 'bootstrap3',

	'css'	=> array(
		'prototype.css',
		'prototype-responsive.css'
	),

    'helpers' => array(
        'factories' => array(
            'layoutClass'                        => 'Swissbib\View\Helper\Swissbib\Factory::getLayoutClass',
        )
    )
);