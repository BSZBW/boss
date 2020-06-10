<?php

/**
 * Record driver view helper
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
 * @package  View_Helpers
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
namespace BszTheme\View\Helper\Bodensee;

/**
 * Record driver view helper
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Record extends \VuFind\View\Helper\Root\Record
{
    /**
     * Client Model
     * @var type \Bsz\Config\Client
     */
    protected $client;

    /**
     *
     * @var \Bsz/Holding
     */
    protected $holding;

    /**
     *
     * @var array
     */
    protected $libraries = false;

    /**
     *
     * @var array;
     */
    protected $ppns = [];

    protected $localIsils = [];

    /**
     *
     * @var bool
     */
    protected $atCurrentLibrary = false;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $config VuFind configuration
     */
    public function __construct($config = null, \Bsz\Config\Client $client, \Bsz\Holding $holding)
    {
        parent::__construct($config);
        $this->client = $client;
        $this->holding = $holding;
        $this->localIsils = $this->client->getIsilAvailability();
    }

    /**
     * Get the CSS class used to properly render a format.  (Note that this may
     * not be used by every theme).
     *
     * @param string $format Format text to convert into CSS class
     *
     * @return string
     */
    public function getFormatClass($format)
    {
        if (is_array($format)) {
            $format = implode(' ', $format);
        }
        return $this->renderTemplate(
                        'format-class.phtml', ['format' => $format]
        );
    }

    /**
     *
     *
     * @param bool $openUrlActive Is there an active OpenURL on the page?
     *
     * @return array
     */
    public function getLinkDetails($openUrlActive = false)
    {
        $sources = parent::getLinkDetails($openUrlActive);
        foreach ($sources as $k => $array) {
            if (isset($array['desc']) && strlen($array['desc']) > 60) {
                $array['desc'] = substr($array['desc'], 0, 60) . '...';
                $sources[$k] = $array;
            }
        }
        return $sources;
    }

    /**
     * Generate a thumbnail URL (return false if unsupported).
     *
     * @param string $size Size of thumbnail (small, medium or large -- small is
     * default).
     *
     * @return string|bool
     */
    public function getThumbnail($size = 'small')
    {
        // Try to build thumbnail:
        $thumb = $this->driver->tryMethod('getThumbnail', [$size]);

        if (empty($thumb)) {
            return false;
        }

        if (is_array($thumb)) {
            if (array_key_exists('issn', $thumb)) {
                return false;
            }
        }

        // Array?  It's parameters to send to the cover generator:
        if (is_array($thumb)) {
            $urlHelper = $this->getView()->plugin('url');
            return $urlHelper('cover-show') . '?' . http_build_query($thumb);
        }

        // Default case -- return fixed string:
        return $thumb;
    }

    /**
     * Determin if an item is available locally
     *
     * @param $webservice = false
     *
     * @return boolean
     */
    public function isAtCurrentLibrary($webservice = false)
    {
        $status = false;
        $network = $this->driver->getNetwork();

        if (count($this->ppns) == 0) {
            // if we have local holdings, item can't be ordered
            if ($this->hasLocalHoldings()) {
                $status = true;
            } elseif ($webservice && $network == 'SWB'
                 && $this->hasParallelEditions()
            ) {
                $status = true;
            } elseif ($webservice && $network !== 'SWB'
                && $this->queryWebservice()
            ) {
                $status = true;
            }
        }
        if ($this->hasLocalHoldings() && $network == 'ZDB') {
            $this->queryWebservice();
        }
        // we dont't want to do the query twice, so we save the status
        $this->atCurrentLibrary = $status;
        return $status;
    }

    /**
     * Check if the item should have an ill button
     * @return boolean
     */
    public function isAvailableForInterlending()
    {
        $ppn = $this->driver->getPPN();
        $network = $this->driver->getNetwork();
        // first, the special cases
        if (($network == 'HEBIS' && preg_match('/^8/', $ppn))) {
            // HEBIS items with 8 at the first position are freely available
            return false;
        } elseif ($this->driver->isFree()) {
            return false;
        } elseif (($this->driver->isArticle()
            // printed journals, articles, newspapers - show hint
            || $this->driver->isJournal()
            || $this->driver->isNewspaper()) && !$this->driver->isElectronic()
        ) {
            return true;
        } elseif ($this->driver->isEBook()) {
            return false;
        } elseif ($this->driver->isJournal() && $this->driver->isElectronic() && ($network == 'SWB' || $network == 'ZDB')) {
            return $this->checkIllIndicator(['e', 'b', ]);
        } elseif ($this->driver->isMonographicSerial() || $this->driver->isEBook()) {
            return false;
        }

        // if we arrived here, item is not available at current library, is no
        // serial and no collection, it is available

        if (!$this->isAtCurrentLibrary(true)
                && !$this->driver->isSerial()
                && !$this->driver->isCollection()) {
            return true;
        }
        return false;
    }

    /**
     * Renders FIS Logo with link
     * @return string
     */
    public function getFisLink()
    {
        return $this->renderTemplate('fis.phtml');
    }

    /**
     * Query webservice to get SWB hits with the same
     * <ul>
     * <li>ISSN or ISBN (preferred)</li>
     * <li>Title, author and year (optional)</li>
     * </ul>
     * Found PPNs are added to ppns array and can be accessed by other methods.
     *
     * @return boolean
     */
    protected function queryWebservice()
    {

        // set up query params
        $this->holding->setNetwork('DE-576');
        $isbn = $this->driver->getCleanISBN();
        $years = $this->driver->getPublicationDates();
        $zdb = $this->driver->tryMethod('getZdbId');
        $year = array_shift($years);

        if ($this->driver->isArticle() || $this->driver->isJournal()
                || $this->driver->isNewspaper()
            ) {
            // prefer ZDB ID
            if (!empty($zdb)) {
                $this->holding->setZdbId($zdb);
            } else {
                $this->holding->setIsxns($this->driver->getCleanISSN());
            }
            // use ISSN and year
        } elseif (!empty($isbn)) {
            // use ISBN and year
            $this->holding->setIsxns($isbn)
                            ->setYear($year);
        } else {
            // use title and author and year
            $this->holding->setTitle($this->driver->getTitle())
                          ->setAuthor($this->driver->getPrimaryAuthor())
                          ->setYear($year);
        }
        // check query and fire
        if ($this->holding->checkQuery()) {
            $result = $this->holding->query();
            // check if any ppn is available locally
            if (isset($result['holdings'])) {
                // search for local available PPNs
                foreach ($result['holdings'] as $ppn => $holding) {
                    foreach ($holding as $entry) {
                        if (isset($entry['isil']) && in_array($entry['isil'], $this->localIsils)) {
                            // save PPN
                            $this->ppns[] = '(DE-627)' . $ppn;
                            $this->libraries[] = $entry['isil'];
                        }
                    }
                }
            }
            // if no locally available ppn found, just take the first one
            if (count($this->ppns) < 1 && isset($result['holdings'])) {
                reset($result['holdings']);
                $this->ppns[] = '(DE-627)' . key($result['holdings']);
            }
        }

        // check if any of the isils from webservic matches local isils
        if (is_array($this->libraries) && count($this->libraries) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Simply checks if there are local holdings available in field 924
     *
     * @return boolean
     */
    protected function hasLocalHoldings()
    {

        // First, simple checks using fiels 924
        $localHoldings = $this->driver->tryMethod('getLocalHoldings');
        if (count($localHoldings) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Quer< solr for parallel Editions available at local libraries
     * Save the found PPNs in global array
     *
     * @return boolean
     */
    protected function hasParallelEditions()
    {
        $ppns = [];
        $related = $this->driver->tryMethod('getRelatedEditions');
        $hasParallel = false;

        foreach ($related as $rel) {
            $ppns[] = $rel['id'];
        }
        $parallel = [];
        if (count($ppns) > 0) {
            $parallel = $this->holding->getParallelEditions($ppns, $this->client->getIsilAvailability());
            // check the found records for local available isils
            $isils = [];
            foreach ($parallel->getResults() as $record) {
                $f924 = $record->getField924();
                $recordIsils = array_keys($f924);
                $isils = array_merge($isils, $recordIsils);
            }
            foreach ($isils as $isil) {
                if (in_array($isil, $this->localIsils)) {
                    $hasParallel = true;
                    $this->ppns[] = $record->getUniqueId();
                }
            }
        }
        return $hasParallel;
    }

    /**
     * Determine if a record is available at the first ISIL or at it's
     * institutes. In opposite to isAtCurrentLibrary, we do not include other
     * libraries (=other ISILs) here.
     * @param string $isil
     */
    public function isAtFirstIsil()
    {
        $holdings = $this->driver->tryMethod('getLocalHoldings');
        $allIsils = $this->client->getIsilAvailability();
        $firstIsil = reset($allIsils);

        foreach ($holdings as $holding) {
            if (preg_match("/(^$firstIsil\$)|($firstIsil)[-\/\s]+/", $holding['b'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Feturn found SWB IDs
     *
     * @return array
     */
    public function getSwbId()
    {
        return array_unique($this->ppns);
    }

    /**
     * Do we have a SWB PPN
     *
     * @return boolean
     */
    public function hasSwbId()
    {
        return count($this->ppns) > 0;
    }

    /**
     * Render a sub record to be displayed in a search result list.
     *
     * @author <dku@outermedia.de>
     * @return string the rendered sub record
     */
    public function getSubRecord()
    {
        return $this->renderTemplate('result-list.phtml');
    }

    /**
     * Check the ILL indicator
     * @param array $allowedCodes
     * @return boolean
     */
    protected function checkIllIndicator($allowedCodes)
    {
        $f924 = $this->driver->tryMethod('getField924');
        foreach ($f924 as $field) {
            if (isset($field['d']) && in_array($field['d'], $allowedCodes)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get HTML to render a title.
     *
     * @param int $maxLength Maximum length of non-highlighted title.
     *
     * @return string
     */
    public function getTitleHtml($maxLength = 180)
    {
        $highlightedTitle = $this->driver->tryMethod('getHighlightedTitle');
        if (is_array($this->driver->tryMethod('getTitle'))) {
            $title = trim($this->driver->tryMethod('getTitle')[0]);
        } else {
            $title = trim($this->driver->tryMethod('getTitle'));
        }

        if (!empty($highlightedTitle)) {
            $highlight = $this->getView()->plugin('highlight');
            $addEllipsis = $this->getView()->plugin('addEllipsis');
            return $highlight($addEllipsis($highlightedTitle, $title));
        }
        if (!empty($title)) {
            $escapeHtml = $this->getView()->plugin('escapeHtml');
            $truncate = $this->getView()->plugin('truncate');
            return $escapeHtml($truncate($title, $maxLength));
        }
        $transEsc = $this->getView()->plugin('transEsc');
        return $transEsc('Title not available');
    }
}
