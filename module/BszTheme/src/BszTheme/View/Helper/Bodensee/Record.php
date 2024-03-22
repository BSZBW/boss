<?php

/*
 * Copyright 2020 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
namespace BszTheme\View\Helper\Bodensee;

use Laminas\Config\Config;

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
    protected $iconconfig;

    /**
     * Constructor
     *
     * @param Config $config VuFind configuration
     * @param array $localIsils
     */
    public function __construct(
        $config = null,
        Config $iconconfig = null,
        $localIsils = []
    ) {
        parent::__construct($config);
        $this->localIsils = $localIsils;
        $this->iconconfig = $iconconfig;
    }

    /**
     * Get the CSS class used to properly render a format.  (Note that this may
     * not be used by every theme).
     *
     * @param string $format Format text to convert into CSS class
     *
     * @return string
     */
    public function getFormatClass($formats = [])
    {
        if (empty($formats)) {
            $formats = $this->driver->getFormats();
        }
        if (is_array($formats)) {
            $formats = implode(' ', $formats);
        }
        return $this->renderTemplate(
            'format-class.phtml',
            ['format' => $formats]
        );
    }

    /**
     * Get the icon CSS class from
     *
     * @param array $formats
     *
     * @return string
     */
    public function getFormatIcon($formats = [])
    {
        if (empty($formats)) {
            $formats = $this->driver->getFormats();
        }
        $retval = 'sonstiges';

        $formatStr = implode('_', $formats);

        if (isset($this->iconconfig) && $this->iconconfig->offsetExists($formatStr)) {
            $retval = $this->iconconfig->get($formatStr);
        }
        return $retval;

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

    public function getLinkDetails2()
    {
        $openUrl = $this->getView()->plugin('openUrl');
        $openUrlActive = $openUrl($this->driver, 'record')->isActive();
        return $this->getLinkDetails($openUrlActive);
    }

    public function getOpenUrlTemplate()
    {
        $openUrl = $this->getView()->plugin('openUrl');
        $openUrl = $openUrl($this->driver, 'record');
        if($openUrl->isActive()) {
            return $openUrl->renderTemplate();
        }
        return '';
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
//        $holdings = $this->driver->tryMethod('getLocalHoldings');
        $holdings = $this->driver->tryMethod('getHoldingIsils');
        $firstIsil = reset($this->localIsils);

        foreach ($holdings as $holding) {
            if (preg_match("/(^$firstIsil\$)|($firstIsil)[-\/\s]+/", $holding)) {
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
    public static function indicator2status($indicator)
    {
        return 'ILL::status_' . $indicator;
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

    /**
     * Render a search result for the specified view mode.
     *
     * @param string $view View mode to use.
     * @param int    $num  rECORD NUMBER
     * @return string
     */
    public function getSearchResult($view, $num = null)
    {
        return $this->renderTemplate(
            'result-' . $view . '.phtml',
            [
                'recordNumber' => $num,
                'driver' => $this->driver
            ]
        );
    }

    public function getLabels()
    {
        $retVal = [];
        if($this->driver->tryMethod('isEPflicht')) {
            $retVal[] = [
                'class' => 'label-warning',
                'short' => 'ePflicht-BW Label',
                'long' => 'ePflicht-BW Text'
            ];
        }
        if($this->driver->tryMethod('isLFER')) {
            $retVal[] = [
                'class' => 'label-lfer',
                'short' => 'lizenzfrei',
                'long' => 'LFER_long'
            ];
        }

        return $retVal;
    }
}
