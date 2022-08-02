<?php

namespace BszTheme;

use Laminas\Http\Request;
use Laminas\ServiceManager\ServiceManager;

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
        $request = $sm->get('Request');
        $tag = 'swb';
        if ($request instanceof Request) {
            $host = $request->getHeaders()->get('host')->getFieldValue();

            if (preg_match('/ireon-portal/', $host)) {
                $tag = 'ireon';
            } else {
                $parts = explode('.', $host);
                $tag = $parts[0] ?? 'swb';
            }
        }
        return new ThemeInfo(realpath(APPLICATION_PATH . '/themes'), 'bodensee', $tag);
    }
}
