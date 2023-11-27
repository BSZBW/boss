<?php

namespace BszTheme;

use Psr\Container\ContainerInterface;

/**
 * VuFind creates its ThemeInfo in a dynamic way. We use a factory here
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class ThemeInfoFactory extends \VuFindTheme\ThemeInfoFactory
{
    /**
     * Create ThemeInfo instance
     *
     * @param ContainerInterface $container
     *
     * @return ThemeInfo
     */
    public static function getThemeInfo(ContainerInterface $container)
    {
        $config = $container->get('Bsz\Config\Client');
        $tag = 'swb';
        if (!$config->get('Site')->offsetExists('tag')) {
            $url = $config->get('Site')->get('url');
            $parts = parse_url($url);
            $host = $parts['host'];
            $domainParts = explode('.', $host);
            $tag = $domainParts[0] ?? 'swb';
        } elseif ($config->get('Site')->offsetExists('tag')) {
            $tag = $config->get('Site')->get('tag');
        }
        $themeInfo =  new ThemeInfo(realpath(APPLICATION_PATH . '/themes'), 'bodensee', $tag);

        $cacheConfig = [
            'adapter' => \Laminas\Cache\Storage\Adapter\Memory::class,
            'options' => ['memory_limit' => -1],
        ];
        $cache = $container->get(\Laminas\Cache\Service\StorageAdapterFactory::class)
            ->createFromArrayConfiguration($cacheConfig);

        $themeInfo->setCache($cache);

        return $themeInfo;
    }
}
