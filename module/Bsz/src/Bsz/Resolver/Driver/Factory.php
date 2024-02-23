<?php
namespace Bsz\Resolver\Driver;

use Interop\Container\ContainerInterface;

/**
 * Factory for Resolver Drivers
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{
    /**
     * Factory for Ezb record driver.
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return Ezb
     */
    public static function getEzb(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config');
        return new Ezb(
            $config->OpenURL->url,
            $container->get('VuFind\Http')->createClient(),
            $container->get(\VuFind\Net\UserIpReader::class),
            'bibid=' . $config->OpenURL->bibid
        );
    }

    /**
     * Factory for Redi record driver.
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return Redi
     */
    public static function getRedi(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config');
        return new Redi(
            $config->OpenURL->url,
            $container->get('VuFind\Http')->createClient()
        );
    }

    /**
     * Factory for Ezb record driver.
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return Ezb
     */
    public static function getIll(ContainerInterface $container)
    {
        $libraries = $container->get('Bsz\Config\Libraries');
        // This is a special solution for UB Heidelberg
        $library = $libraries->getByIsil('DE-16');
        return new Ill(
            $library->getCustomUrl(),
            $container->get('VuFind\Http')->createClient(),
            $container->get(\VuFind\Net\UserIpReader::class)
        );
    }
}
