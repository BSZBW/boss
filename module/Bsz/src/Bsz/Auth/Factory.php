<?php
namespace Bsz\Auth;

use Interop\Container\ContainerInterface;
use VuFind\Auth\Shibboleth\MultiIdPConfigurationLoader;
use VuFind\Auth\Shibboleth\SingleIdPConfigurationLoader;

/**
 * Description of Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{
    const SHIBBOLETH_CONFIG_FILE_NAME = 'Shibboleth';
    /**
     * Construct the authentication manager.
     *
     * @param ContainerInterface $container
     *
     * @return Manager
     */
    public static function getManager(ContainerInterface $container)
    {
        // Set up configuration:
        $config = $container->get('VuFind\Config')->get('config');
        $client = $container->get('Bsz\Config\Client');
        $library = null;
        if ($client->isIsilSession()) {
            $library = $client->getLibrary();
        }
        try {
            // Check if the catalog wants to hide the login link, and override
            // the configuration if necessary.
            $catalog = $container->get('VuFind\ILSConnection');
            if ($catalog->loginIsHidden()) {
                $config = new \Laminas\Config\Config($config->toArray(), true);
                $config->Authentication->hideLogin = true;
                $config->setReadOnly();
            }
        } catch (\Exception $e) {
            // Ignore exceptions; if the catalog is broken, throwing an exception
            // here may interfere with UI rendering. If we ignore it now, it will
            // still get handled appropriately later in processing.
            error_log($e->getMessage());
        }

        // Load remaining dependencies:
        $userTable = $container->get('VuFind\DbTablePluginManager')->get('user');
        $sessionManager = $container->get('VuFind\SessionManager');
        $pm = $container->get('VuFind\AuthPluginManager');
        $cookies = $container->get('VuFind\CookieManager');
        $csrf = $container->get(\VuFind\Validator\Csrf::class);

        // Build the object and make sure account credentials haven't expired:
        $manager = new Manager($config, $userTable, $sessionManager, $pm, $cookies, $csrf, $library);
        $manager->checkForExpiredCredentials();
        return $manager;
    }

    public function __invoke(ContainerInterface $container, $requestedName) {
        $client = $container->get('Bsz\Config\Client');
        return new $requestedName(
            $container->get('VuFind\SessionManager'),
            $container->get('Bsz\Config\Libraries'),
            $client->getIsils()
        );
    }

}
