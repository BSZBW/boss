<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
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
 */
namespace BszTheme\View\Helper\Bodensee;

use Bsz\RecordDriver\Constants as Def;
use Bsz\RecordDriver\SolrMarc;

/**
 * Extension of Root RecordLink Helper
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class RecordLink extends \VuFind\View\Helper\Root\RecordLinker
{
    /**
     *
     * @var \VuFind\Config\config
     */
    protected $config;
    /**
     *
     * @var string
     */
    protected $baseUrl;

    public function __construct(\VuFind\Record\Router $router, \Laminas\Config\Config $config, $baseUrl = null)
    {
        parent::__construct($router);
        $this->baseUrl = $baseUrl;
        $this->config = $config;
    }

    public function getCoverServiceUrls($driver)
    {
        $services = [];
        $sources = $this->config->get('CoverSources');

        foreach ($sources as $source => $url) {
            $isxn = strlen($driver->getCleanISSN()) > 0 ?
                    $driver->getCleanISSN() : $driver->getCleanISBN();
            if (strlen($isxn) > 0) {
                $services[$source] = sprintf($url, $isxn);
            }
        }
        return $services;
    }

    public function linkPPN(SolrMarc $driver, $url = '')
    {
        $props = $this->determineProperties($driver, $url);
        return $this->getView()->render('Helpers/ppn.phtml', $props);
    }

    public function linkPPNButton(SolrMarc $driver, $url = '')
    {
        $props = $this->determineProperties($driver, $url);
        return $this->getView()->render('Helpers/ppnButton.phtml', $props);
    }

    /**
     *
     * @param \BszTheme\View\Helper\Bodensee\Bsz\RecordDriver\SolrMarc $driver
     * @param type $url
     * @return array
     */
    private function determineProperties(SolrMarc $driver, $url = '')
    {
        $id = $driver->getUniqueId();
        $ppn = preg_replace('/\(.*\)/', '', $id);
        $recordHelper = $this->getView()->plugin('record');
        $label = '';
        $url = empty($url) ? $this->baseUrl : $url;

        // if the record is available at the first ISIL, either link an external
        // url or use the baseUrl (which is normally the aDIS URL of the current
        // library).
        // otherwise use the network OPAC urls, which can be found in BSZ.ini

        if ($driver->getNetwork() == 'SWB' && $recordHelper->isAtFirstIsil()) {
            $url = str_replace('%PPN%', $ppn, $url);
            $label = 'ILL::library_opac';;
        } else {
            $label = 'ILL::check_availability_network_opac';
            $opacList = $this->config->get('OPAC')->toArray();
            $network = $driver->getNetwork();
            if (array_key_exists($network, $opacList)) {
                $url = $opacList[$network];
                $url = str_replace('%PPN%', $ppn, $url);
            }
        }
        return [
            'link' => $url,
            'ppn' => $ppn,
            'label' => $label
        ];
    }

    /**
     * This method renders the author names well formated as HTML
     *
     * @param SolrMarc $driver
     * @param int $number 1 to number of authors
     *
     * @return string
     */
    public function linkAuthor(SolrMarc $driver, int $number) : string
    {
        $params = [];
        if ($number == 1) {
            $params = [
                'gnd' => $driver->getPrimaryAuthor(Def::AUTHOR_GND),
                'name' => $driver->getPrimaryAuthor(Def::AUTHOR_NAME),
                'live' => $driver->getPrimaryAuthor(Def::AUTHOR_LIVE),
            ];
        } else {
            $number--;
            $params = [
                'gnd' => $driver->getSecondaryAuthor(Def::AUTHOR_GND, $number),
                'name' => $driver->getSecondaryAuthor(Def::AUTHOR_NAME, $number),
                'live' => $driver->getSecondaryAuthor(Def::AUTHOR_LIVE, $number),
            ];
        }
        return $this->getView()->render('Helpers/singleauthor.phtml', $params);
    }

    public function getChildSearchUrl($driver, $filterString)
    {
        $urlHelper = $this->getView()->plugin('url');
        $route = $this->getSearchActionForSource($driver->getSourceIdentifier());
        $id = urlencode(addcslashes($driver->getUniqueID(), '"'));

        $filter = urlencode(addcslashes($filterString, '"'));
        $filter = empty($filterString) ? '' :  '&hiddenFilters[]=' .$filter;
        return $urlHelper($route)
            . '?lookfor=' . $id
            . '&type=ParentID'
            . '&sort=publishDateSort desc, id asc'
            . '&hiddenFilter[]=-id:' . $id
            . $filter;
    }

}
