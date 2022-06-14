<?php
/**
 * SearchButtons Recommendations Module
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Recommendations
 * @author   Lutz Biedinger <lutz.biedinger@gmail.com>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:recommendation_modules Wiki
 */
namespace Bsz\Recommend;

/**
 * EuropeanaResults Recommendations Module
 *
 * This class provides recommendations by using the WorldCat Terminologies API.
 *
 * @category VuFind2
 * @package  Recommendations
 * @author   Lutz Biedinger <lutz.biedinger@gmail.com>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:recommendation_modules Wiki
 */
class SearchButtons implements \VuFind\Recommend\RecommendInterface,
    \VuFindHttp\HttpServiceAwareInterface, \Zend\Log\LoggerAwareInterface
{
    use \VuFind\Log\LoggerAwareTrait;
    use \VuFindHttp\HttpServiceAwareTrait;

    /**
     * Request parameter for searching
     *
     * @var string
     */
    protected $requestParam;

    /**
     * Result limit
     *
     * @var int
     */
    protected $limit;

    /**
     * Europeana base URL
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
    protected $key;

    /**
     * Search string
     *
     * @var string
     */
    protected $lookfor;

    /**
     * Search results
     *
     * @var array
     */
    protected $results;

    /**
     * Source title
     *
     * @var string
     */
    protected $sourceTitle;

    /**
     * Image name
     *
     * @var string
     */
    protected $imageName;

    /**
     * http / https
     *
     * @var string
     */
    protected $protocol;

    /**
     * img column Number for class Attribute
     *
     * @var string
     */
    protected $colNumber;

    /**
     * label for header above searchbutton
     *
     * @var string
     */
    protected $label;

    /**
     * type of searchbutton
     *  'link' = do not append searchterms to link
     *  '' = append searchterms to link
     * @var string
     */
    protected $type;

    /**
     * Constructor
     *
     * @param string $key API key
     */
    public function __construct($key)
    {
        $this->key = $key;
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
        // Parse out parameters:
        // fieldname[] = "SearchButtons:baseurl:[title]:[image filename]:[colNumber]:[label]"
        $params = explode(':', $settings);

        $this->protocol = (isset($params[0]) && !empty($params[0]))
            ? $params[0] : 'http';

        $this->baseUrl = (isset($params[1]) && !empty($params[1]))
            ? $params[1] : 'fernleihe.boss.bsz-bw.de/Search/Results?lookfor=';

        $this->sourceTitle = (isset($params[2]) && !empty($params[2]))
            ? $params[2] : 'Search in external source';

        $this->imageName = (isset($params[3]) && !empty($params[3]))
            ? $params[3] : 'default.png';

        $this->colNumber = (isset($params[4]) && !empty($params[4]))
            ? $params[4] : '';

        $this->label = (isset($params[5]) && !empty($params[5]))
            ? $params[5] : '';

        $this->type = (isset($params[6]) && !empty($params[6]))
            ? $params[6] : '';
    }

    /**
     * Build the url which will be send to retrieve the RSS results
     *
     * @return string The url to be sent
     */
    public function getURL()
    {
        return $this->targetUrl;
    }

    /**
     * Fetch the sourceTitle
     *
     * @return string sourceTitle   The title of the source
     */
    public function getSourceTitle()
    {
        return $this->sourceTitle;
    }

    /**
     * Fetch the filename of the logo
     *
     * @return string imageName   The filename of the image
     */
    public function getImageName()
    {
        return $this->imageName;
    }

    /**
     * Fetch the number of div-class for image grid-layout
     *
     * Example:
     * <div class="col-xs-12"> --> number = 12  (one img per row)
     * <div class="col-xs-6"> --> number = 6 (two images per row)
     *
     * @return string pixelHeight
     */
    public function getColNumber()
    {
        return $this->colNumber;
    }

    /**
     * Fetch the label for header above searchbutton
     *
     * @return string label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * }
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
        if ($this->type === 'link') {
            $this->targetUrl = $this->protocol . '://' . $this->baseUrl;
        } else {
            // Collect the best possible search term(s):
            $this->lookfor = urlencode(trim($this->lookfor));
            $this->lookfor =  $request->get('lookfor', '');
            if (empty($this->lookfor) && is_object($params)) {
                $this->lookfor = $params->getQuery()->getAllTerms();
            }
            if (strpos($this->baseUrl, '<') !== false) {
                $this->baseUrl = preg_replace('/<.*>/', $this->lookfor, $this->baseUrl);
                $this->targetUrl = $this->protocol . '://' . $this->baseUrl;
            } else {
                $this->targetUrl = $this->protocol . '://' . $this->baseUrl . $this->lookfor;
            }
        }
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
    }

    /**
     * Get the results of the query (false if none).
     *
     * @return array|bool
     */
    public function getResults()
    {
        /* return $this->results; */
    }
}
