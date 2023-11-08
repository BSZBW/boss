<?php
/**
 * RSS Feed Recommendations Module
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  Recommendations
 * @author   Lutz Biedinger <lutz.biedinger@gmail.com>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
namespace Bsz\Recommend;

use Laminas\Feed\Reader\Reader as FeedReader;

/**
 * RSS Feed  Recommendations Module
 * This class provides recommendations by using the RSS Feeds API.
 * @category VuFind
 * @package  Recommendations
 * @author   Stefan Winkler <stefan.winkler@bsz-bw.de>
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:recommendation_modules Wiki
 */
class RSSFeedResults implements \VuFind\Recommend\RecommendInterface,
    \VuFindHttp\HttpServiceAwareInterface, \Laminas\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Result limit
     *
     * @var int
     */
    protected $limit;

    /**
     * RSS base URL
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * Fully constructed API URL
     *
     * @var string
     */
    protected $targetUrl;

    /**
     * Site to search
     *
     * @var string
     */
    protected $searchSite;

    /**
     * Link for more results
     *
     * @var string
     */
    protected $sitePath;

    /**
     * API key
     *
     * @var string
     */
    protected $feed;

    /**
     * Search results
     * @var array
     */
    protected $results;

    protected $htmlpurifier;

    /**
     * [StartpageNews] in searches.ini
     *
     * @param string $feed
     */
    public function __construct($feed)
    {
        $this->feed = $feed;
    }

    /**
     * Attach HTMLPurifies to sanitize invalid HTML and whitelist tags
     *
     * @param \HTMLPurifier $purifier
     */
    public function attachHtmlPurifier(\HTMLPurifier $purifier)
    {
        $this->htmlpurifier = $purifier;
    }

    /**
     * Store the configuration of the recommendation module.
     *
     * @param string $settings Settings from searches.ini.
     *
     * @return void
     */
    public function setConfig($settings)
    {
        // We have two possible configs in searches.ini:
        //
        // [StartpageNews]
        // RSSFeed=[url]:[limit]
        //
        // [SideRecommendations]
        // AllFields[]=RSSFeedResultsDeferred:[url]:[limit]

        if ($settings === null) {
            $settings = $this->feed;
        }

        // Parse out parameters:
        list($url, $limit, $title) = explode(':', $settings);
        $this->baseUrl = $url;
        $this->limit = $limit ?? 5;
        $this->searchSite = $title ?? '';
    }

    /**
     * Called at the end of the Search Params objects' initFromRequest() method.
     * This method is responsible for setting search parameters needed by the
     * recommendation module and for reading any existing search parameters that may
     * be needed.
     *
     * @param \VuFind\Search\Base\Params $params  Search parameter object
     * @param \Zend\StdLib\Parameters    $request Parameter object representing user
     * request.
     *
     * @return void
     */
    public function init($params, $request)
    {
        $this->targetUrl = 'https://' . $this->baseUrl;
    }

    /**
     * Called after the Search Results object has performed its main search.  This
     * may be used to extract necessary information from the Search Results object
     * or to perform completely unrelated processing.
     *
     * @param \VuFind\Search\Base\Results $results Search results object
     *
     * @return void
     */
    public function process($results)
    {
        $this->debug('Pulling feed from ' . $this->targetUrl);
        if (null !== $this->httpService) {
            FeedReader::setHttpClient(
                $this->httpService->createClient($this->targetUrl)
            );
        }
        $parsedFeed = FeedReader::import($this->targetUrl);
        $resultsProcessed = [];

        foreach ($parsedFeed as $value) {

            if (is_object($this->htmlpurifier)) {
                $clean_html = $this->htmlpurifier->purify($value->getDescription());
            }
            $resultsProcessed[] = [
                'title' => $value->getTitle(),
                'link' => $value->getLink(),
                'enclosure' => $value->getEnclosure()['url'],
                'description' => $clean_html,
                'date' => $value->getDateCreated(),
                'author' => $value->getAuthor(),
                'categories' => $value->getCategories(),

            ];

            if (count($resultsProcessed) == $this->limit) {
                break;
            }
        }

        if (!empty($resultsProcessed)) {
            $this->results = [
                'worksArray' => $resultsProcessed,
                'feedTitle' => $this->searchSite,
                'sourceLink' => $this->sitePath
            ];
        } else {
            $this->results = false;
        }
    }

    /**
     * Get the results of the query (false if none).
     *
     * @return array|bool
     */
    public function getResults()
    {
        return $this->results;
    }
}
