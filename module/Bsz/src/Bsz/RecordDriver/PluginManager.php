<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bsz\RecordDriver;

use VuFind\RecordDriver\AbstractBase;

/**
 * We need to change the recordtype -> RecordDriver mapping
 * @author amzar
 */
class PluginManager extends \VuFind\RecordDriver\PluginManager
{
    /**
     * Convenience method to retrieve a populated Solr record driver.
     *
     * @param array  $data             Raw Solr data
     * @param string $keyPrefix        Record class name prefix
     * @param string $defaultKeySuffix Default key suffix
     *
     * @return AbstractBase
     */
    public function getSolrRecord($data, $keyPrefix = 'Solr',
        $defaultKeySuffix = 'Default'
    ) {
        $key = $keyPrefix . ucwords(
            $data['record_format'] ?? $data['recordtype'] ?? $defaultKeySuffix
        );
        $key = preg_replace('/Search2/','Solr', $key);
        $recordType = $this->has($key) ? $key : $keyPrefix . $defaultKeySuffix;

        // Findex also sends recordtype=SolrMarc, so, we need to distinguish
        //  between Findex and other sources.
        if (!preg_match('/Gvi|Ntrs|Finc/i', $recordType)) {
            $recordType = 'SolrFindexMarc';
        }

        // Build the object:
        $driver = $this->get($recordType);
        $driver->setRawData($data);
        return $driver;
    }
}
