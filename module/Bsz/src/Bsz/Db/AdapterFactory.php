<?php

namespace Bsz\Db;

use Laminas\Db\Adapter\Adapter;

class AdapterFactory extends  \VuFind\Db\AdapterFactory
{
    //CopyFromVufind
    public function getAdapterFromConfig($overrideUser = null, $overridePass = null){

        if (!isset($this->config->Database)) {
            throw new \Exception('[Database] Configuration section missing');
        }

        $config = $this->config->Database;
        $hostname = $config->database_host ?? null;

        if (isset($config->database_host_file)){
            $file = $config->database_host_file;

            if(!is_readable($file)){
                throw new \Exception('File is not readable: ' . $file);
            }
            $hostname = trim(file_get_contents($file));
        }

        return $this->getAdapterFromArray([
            'driver' => $config->database_driver ?? null,
            'hostname' => $hostname,
            'username' => $overrideUser ?? $config->database_username ?? null,
            'password' => $overridePass ?? $this->getSecretFromConfig($config, 'database_password'),
            'database' => $config->database_name,
            'port' => $config->database_port ?? null,
        ]);
    }
}