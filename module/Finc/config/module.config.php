<?php

namespace Finc\Module\Config;

$config = [
    'vufind' => [
        'plugin_managers' => [
            'recorddriver'  => [
                'factories' => [
                    'Finc\RecordDriver\SolrMarc' => 'Finc\RecordDriver\Factory::getSolrMarc',
                    'Finc\RecordDriver\SolrMarcFinc' => 'Finc\RecordDriver\Factory::getSolrMarcFinc',                    
                    'Finc\RecordDriver\SolrDefault'    => 'Finc\RecordDriver\Factory',
                    'Finc\RecordDriver\SolrAI'         => 'Finc\RecordDriver\Factory',
                    'Finc\RecordDriver\SolrIS'         => 'Finc\RecordDriver\Factory',
                ],
                'aliases' => [                    
                    'solrmarc'              =>  'Finc\RecordDriver\SolrMarc',                    
                    'solrmarcfinc'              =>  'Finc\RecordDriver\SolrMarcFinc',                    
                    'solrdefault'              =>  'Finc\RecordDriver\SolrDefault',                    
                    'solrai'                   =>  'Finc\RecordDriver\SolrAI',                    
                    'solris'                   =>  'Finc\RecordDriver\SolrIS',                    
                ],
                'delegators' => [
                //    'Finc\RecordDriver\SolrMarc' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                //    'Finc\RecordDriver\SolrMarcFinc' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                //    'Finc\RecordDriver\SolrDefault' => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                //    'Finc\RecordDriver\SolrAI'      => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                //    'Finc\RecordDriverSolrIS'       => [\VuFind\RecordDriver\IlsAwareDelegatorFactory::class],
                ]
            ],
        ]
    ],
    'service_manager' => [
        'factories' => [
            'Finc\RecordDriver\PluginManager' => 'Finc\RecordDriver\PluginManagerFactory',
            
        ],
        'aliases' => [
            'VuFind\RecordDriver\PluginManager' => 'Finc\RecordDriver\PluginManager'

        ]
    ],    
    'view_manager' => [
        'display_exceptions'       => APPLICATION_ENV == 'development',
    ],
];
return $config;

