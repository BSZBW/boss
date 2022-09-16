<?php

namespace BszTheme;

use Zend\Http\Request;
use Zend\ServiceManager\ServiceManager;

/**
 * VuFind creates its ThemeInfo in a dynamic way. We use a factory here
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class ThemeInfoFactory extends \VuFindTheme\ThemeInfoFactory
{
    /**
     * Create ThemeInfo instance
     *
     * @param ServiceManager $sm
     *
     * @return ThemeInfo
     */
    public static function getThemeInfo(ServiceManager $sm)
    {
        $config = $sm->get('Bsz\Config\Client');
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
        return new ThemeInfo(realpath(APPLICATION_PATH . '/themes'), 'bodensee', $tag);
    }
}
