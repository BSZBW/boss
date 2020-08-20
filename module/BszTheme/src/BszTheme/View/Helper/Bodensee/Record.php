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

use Zend\Config\Config;

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
    protected $localIsils;

    /**
     * Constructor
     *
     * @param Config $config VuFind configuration
     * @param array $localIsils
     */
    public function __construct($config = null, $localIsils = [])
    {
        parent::__construct($config);
        $this->localIsils = $localIsils;
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
            'format-class.phtml',
            ['format' => $format]
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
                $array['desc'] = substr($array['desc'], 0, 60).'...';
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
     * Renders FIS Logo with link
     * @return string
     */
    public function getFisLink()
    {
        return $this->renderTemplate('fis.phtml');
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


    /**
     * Determine if a record is available at the first ISIL or at it's
     * institutes. In opposite to isAtCurrentLibrary, we do not include other
     * libraries (=other ISILs) here.
     *
     * @return boolean
     *
     */

    public function isAtFirstIsil()
    {
        $holdings = $this->driver->tryMethod('getLocalHoldings');
        $firstIsil = reset($this->localIsils);

        foreach ($holdings as $holding) {
            if (preg_match("/(^$firstIsil\$)|($firstIsil)[-\/\s]+/", $holding['b'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $indicator
     *
     * @return string
     */
    public static function indicator2Status($indicator)
    {
        return 'ILL::status_'.$indicator;
    }

    /**
     * @param $indicator
     *
     * @return string
     */
    public static function indicator2icon($indicator)
    {
        switch ($indicator) {
            case 'a': $icon = 'fa-check text-success'; break;
            case 'b': $icon = 'fa-check text-success'; break;
            case 'c': $icon = 'fa-check text-success'; break;
            case 'd': $icon = 'fa-times text-danger';  break;
            case 'e': $icon = 'fa-network-wired text-success'; break;
            case 'n':
            case 'N': $icon = 'fa-times text-danger'; break;
            case 'l':
            case 'L': $icon = 'fa-check text-success'; break;
            default: $icon = 'fa_times text-danger';
        }
        return $icon;
    }
}
