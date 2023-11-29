<?php

namespace Bsz\Auth;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Psr\Container\ContainerExceptionInterface as ContainerException;
use Psr\Container\ContainerInterface;
use VuFind\Auth\Shibboleth\MultiIdPConfigurationLoader;
use VuFind\Auth\Shibboleth\SingleIdPConfigurationLoader;

class ShibbolethFactory implements \Laminas\ServiceManager\Factory\FactoryInterface
{
    public const SHIBBOLETH_CONFIG_FILE_NAME = 'Shibboleth';

    /**
     * Create an object
     *
     * @param ContainerInterface $container     Service manager
     * @param string             $requestedName Service being created
     * @param null|array         $options       Extra options (optional)
     *
     * @return object
     *
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     * creating a service.
     * @throws ContainerException&\Throwable if any other error occurs
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ) {
        if (!empty($options)) {
            throw new \Exception('Unexpected options sent to factory.');
        }
        $loader = $this->getConfigurationLoader($container);
        $request = $container->get('Request');
        $client = $container->get('Bsz\Config\Client');
        return new $requestedName(
            $container->get(\Laminas\Session\SessionManager::class),
            $loader,
            $request,
            $container->get('Bsz\Config\Libraries'),
            $client->getIsils()
        );
    }

    /**
     * Return configuration loader for shibboleth
     *
     * @param ContainerInterface $container Service manager
     *
     * @return ConfigurationLoader
     */
    public function getConfigurationLoader(ContainerInterface $container)
    {
        $configManager = $container->get(\VuFind\Config\PluginManager::class);
        $config = $configManager->get('config');
        $override = $config->Shibboleth->allow_configuration_override ?? false;
        $loader = null;
        if ($override) {
            $shibConfig = $configManager->get(self::SHIBBOLETH_CONFIG_FILE_NAME);
            $loader = new MultiIdPConfigurationLoader($config, $shibConfig);
        } else {
            $loader = new SingleIdPConfigurationLoader($config);
        }
        return $loader;
    }
}