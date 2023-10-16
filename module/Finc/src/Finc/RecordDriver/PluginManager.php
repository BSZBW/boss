<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Finc\RecordDriver;

/**
 * We need to change the recordtype -> RecordDriver mapping
 *
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
        $keyPrefix = 'Search2' ? 'Solr' : $keyPrefix;

        $key = $keyPrefix . ucwords(
            $data['record_format'] ?? $data['recordtype'] ?? $defaultKeySuffix
        );
        $recordType = $this->has($key) ? $key : $keyPrefix . $defaultKeySuffix;

        if (!preg_match('/Gvi|Dlr|Ntrs|Finc|Ai|Is/i', $recordType)) {
            $recordType = 'SolrFindexMarc';
        }

        // Build the object:
        $driver = $this->get($recordType);
        $driver->setRawData($data);
        return $driver;
    }
}
