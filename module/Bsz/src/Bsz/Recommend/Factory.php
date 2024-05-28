<?php

/*
 * The MIT License
 *
 * Copyright 2016 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Bsz\Recommend;

use Bsz\Recommend\SideFacets as SideFacets;
use Interop\Container\ContainerInterface;

/**
 * Description of Factors
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{
    /**
     * Factory for SideFacets module.
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return SideFacets
     */
    public static function getSideFacets(ContainerInterface $container)
    {
        $client = $container->get('Bsz\Config\Client');
        $isil = $client->isIsilSession() && $client->hasIsilSession() ? $client->getIsils() : null;

        return new SideFacets(
            $container->get('VuFind\Config'),
            $container->get('VuFind\HierarchicalFacetHelper'),
            $isil
        );
    }

    /**
     * Factory for SearchButtons module.
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return SearchButtons
     */
    public static function getSearchButtons(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('config');
        return new SearchButtons(
            $config->Content->europeanaAPI
        );
    }

    /**
     * Factory for RSSFeed module
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return RSSFeedResults
     */
    public static function getRSSFeedResults(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('searches');
        return new RSSFeedResults(
            $config->get('StartpageNews')->get('RSSFeed')
        );
    }

    /**
     * Factory for News Feed on Startpag
     *
     * @param ContainerInterface $container Service manager.
     *
     * @return RSSFeedResults
     */
    public static function getStartpageNews(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config')->get('searches');

        $rss = new RSSFeedResults(
            $config->get('StartpageNews')->get('RSSFeed')
        );
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p, a, br, strong, em, ul, ol, li, b, i');
        $config->set('HTML.AllowedAttributes', 'a.href');
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Linkify', true);
        $config->set('HTML.AutoParagraph', true);
        $purifier = new \HTMLPurifier($config);

        $rss->attachHtmlPurifier($purifier);

        return $rss;
    }

    public static function getLibraryNews(ContainerInterface $container)
    {
        $client = $container->get('Bsz\Config\Client');
        $libraries = $container->get('Bsz\Config\Libraries');

        $library = $libraries->getFirstActive($client->getIsils());
        $feedUrl = $library->getRss() ? $library->getRss() . ':3' : null;
        $rss = new RSSFeedResults($feedUrl);
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p, a, br, strong, em, ul, ol, li, b, i');
        $config->set('HTML.AllowedAttributes', 'a.href');
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Linkify', true);
        $config->set('HTML.AutoParagraph', true);
        $purifier = new \HTMLPurifier($config);

        $rss->attachHtmlPurifier($purifier);

        return $rss;
    }
}
