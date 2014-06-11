<?php
return array(
	'extends' => 'bootstrap3',

	'css'	=> array(
		'prototype.css'
	),

    'helpers' => array(
        'factories' => array(
            'layoutClass'                        => 'Swissbib\View\Helper\Swissbib\Factory::getLayoutClass',
        )
    )
);