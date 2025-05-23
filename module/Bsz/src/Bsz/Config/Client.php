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
namespace Bsz\Config;

use Exception;
use Laminas\Config\Config;
use Laminas\Config\Reader\Ini;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Session\Container as SessContainer;

/**
 * Client class extends VuFinds configuration to fit our needs.
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Client extends Config
{
    const LOGO_TYPE = '.png';
    const HEADER_TYPE = '.jpg';
    const FAVICON_TYPE = '.ico';

    /**
     *
     * @var Request
     */
    protected $request;

    /**
     *
     * @var Libraries;
     */
    protected $libraries;

    /**
     *
     * @var SessContainer
     */
    protected $sessContainer;

    /**
     * This method is used if object is casted to string
     *
     * @return string
     */
    public function __toString()
    {
        $isils = $this->getIsils();
        return array_shift($isils);
    }

    public function attachSessionContainer(SessContainer $container)
    {
        $this->sessContainer = $container;
    }

    /**
     * Returns Site Title
     * @param string $long = false
     * @return string
     */
    public function getTitle($long = false)
    {
        if ($long) {
            return $this->get('BSZ')->get('title_long');
        } else {
            return $this->get('Site')->get('title');
        }
    }

    /**
     * Returns first part of domain
     * @return string
     */
    public function getTag()
    {
        if ($this->get('Site')->offsetExists('tag')) {
            $tag = $this->get('Site')->get('tag');
        } else {
            $urlParts = explode('.', $this->get('Site')->get('url'));
            $tag = array_shift($urlParts);
            $tag = strtolower($tag);
            $tag = str_replace(['http://', 'https://'], '', $tag);
        }

        return $tag;
    }

    /**
     * Returns footer links for certain box (1-3))
     * @param int $boxNo
     * @return array
     */
    public function getFooterLinks($boxNo)
    {
        $boxName = 'box' . (int)$boxNo;
        $links = $this->get('FooterLinks')->get($boxName);
        if ($boxNo == 2 && !$links) {
            $links[] = '/Search/History';
            $links[] = '/Search/Advanced';
        } elseif ($boxNo == 1 &&
            $this->isIsilSession() &&
            $this->hasIsilSession() &&
            isset($this->libraries)

        ) {
            $library = $this->libraries->getFirstActive($this->getIsils());
            if (isset($library) && $library->getHomepage() !== null) {
                $links[] = isset($library) ? $library->getHomepage() : '';
            }
            if (isset($library)) {
                $links[] =  $library->hasCustomUrl() ? $library->getCustomUrl()
                    : '/Record/Freeform';
            }
        } elseif ($boxNo == 3) {
            $links[] = '/Bsz/Privacy';
        }

        // Clean up urls
        if (is_array($links)) {
            $search = ['[', ']'];
            $replace = ['%5B', '%5D'];

            foreach ($links as $k => $link) {
                $links[$k] = str_replace($search, $replace, $link);
            }
        }
        return $links;
    }

    /**
     * Gibt die Webseite der Institution aus.
     *
     * @param string $mode contactm, imprint, default home page
     * @param null $lang
     *
     * @return string
     */
    public function getWebsite($mode = '', $lang = null)
    {
        if (strlen($mode) == 0) {
            $mode = 'website';
        } else {
            $mode = 'website_' . $mode;
        }

        $website = $this->get('Site')->get($mode) ?? '';

        if (isset($lang) && strpos($website, '%lang%') !== false) {
            $website = str_replace('%lang%', $lang, $website);
        }

        return $website;
    }

    /**
     * Konfiguriert den linken NewsFeed der Startseite
     * @deprecated searches.ini no longer present in this class.
     * @param string
     * @return string
     */
    public function getRSSFeed()
    {
        $feed = null;
        $section = $this->get('StartpageNews');
        if ($section !== null) {
            $feed = $section->get('RSSFeed');
        }
        return $feed;
    }

    /**
     * Returns status of setting
     * @param string $key from config.ini, section BSZSettings
     * @return boolean
     */
    public function is($key)
    {
        $key = trim($key);
        $value = false;

        $tmp = $this->get('Switches')->get($key);
        switch ($tmp) {
            case 'true':
                $value = true;
                break;
            case 'false':
                $value = false;
                break;
            default:
                $value = (bool)$tmp;
        }
        return $value;
    }

    /**
     *
     * @param $request
     * @return Client
     */
    public function attachRequest($request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get ISIL either from session (Interlending view)
     * or from config. As some libraries have multiple isils, it returns an array
     * @return array
     */
    public function getIsils()
    {
        $cookie = null;
        if (isset($this->request)) {
            $cookie = $this->request->getCookie();
        }

        $isils = [];
        if ($this->isIsilSession() && $this->sessContainer->offsetExists('isil')) {
            $isils = (array)$this->sessContainer->offsetGet('isil');
        } elseif ($this->isIsilSession() && isset($cookie->isil)) {
            $isils = explode(',', $cookie->isil);
            // Write isils back to session
            $this->sessContainer->offsetSet('isil', $isils);
        } else {
            $raw = trim($this->get('Site')->get('isil'));
            if (!empty($raw)) {
                $isils = explode(',', $raw);
            }
        }
        return $isils;
    }

    public function getProvenances(): string
    {
        $section = $this->get('Provenances');
        if ($section != null) {
            return trim($section->get('isil'));
        }
        return '*';
    }

    /**
     * Returns Sigel for use in OpenUrl
     * @deprecated not really clear, OpenUrl not used.
     * @return string
     */
    public function getSigel()
    {
        $sigel = '';
        if (!$this->isIsilSession()) {
            $sigel = $this->get('OpenURL')->get('sigel');
        } elseif ($this->libraries instanceof Libraries) {
            $library = $this->libraries->getFirstActive($this->getIsils());
            if ($library instanceof Bsz\Config\Library) {
                $sigel = $library->getSigel();
            }
        }
        return $sigel;
    }

    /**
     * Used on ILL portal do have a set of different isils for availability
     * @return array
     */
    public function getIsilAvailability()
    {
        if ($this->isIsilSession() && isset($this->libraries)) {
            $localIsils = [];
            foreach ($this->libraries->getActive($this->getIsils()) as $library) {
                $localIsils = array_merge($localIsils, $library->getIsilAvailability());
            }
            return array_unique($localIsils);
        } else {
            return $this->getIsils();
        }
    }

    /**
     * Determine if ISIL should be read from session or from Config
     * @return boolean
     */
    public function isIsilSession()
    {
        return (bool)$this->get('Switches')->get('isil_session');
    }

    /**
     * Does this entity alreay have an isil stored in session (ill portal)
     * @return bool
     */
    public function hasIsilSession()
    {
        if ($this->isIsilSession() && count($this->getIsils()) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Add Libraries to thie class
     * @param Libraries $libraries
     */
    public function attachLibraries(Libraries $libraries)
    {
        $this->libraries = $libraries;
        return $this;
    }

    /**
     * returns german library network shourcut
     * @param  string $input
     * @return string
     */
    public function mapNetwork($input)
    {
        $result = '';
        $networks = $this->getNetworks();
        if (array_key_exists($input, $networks)) {
            $result = $networks[$input];
        } elseif (in_array($input, $networks)) {
            // input is already a mapped network name
            $result = $input;
        }
        return $result;
    }

    /**
     * Returns array of all library networks
     * @return array
     */
    public static function getNetworks()
    {
        return [
            'BAW' => 'SWB',
            'BAY' => 'BVB',
            'BER' => 'KOBV',
            'HAM' => 'GBV',
            'HES' => 'HEBIS',
            'NIE' => 'GBV',
            'NRW' => 'HBZ',
            'SAA' => 'GBV',
            'SAX' => 'SWB',
            'THU' => 'GBV',
            'BSZ' => 'SWB',
            'ÖVK' => 'GBV',
            'GVK' => 'GBV',
            'SWB' => 'SWB',
            // Attention:
            // Holdings.php uses array flip. The isils must be at the bottom!
            // Otherwise, holdings won't search correct
            'DE-576' => 'SWB',
            'DE-600' => 'ZDB',
            'DE-601' => 'GBV',
            'DE-602' => 'KOBV',
            'DE-603' => 'HEBIS',
            'DE-604' => 'BVB',
            'DE-605' => 'HBZ',
            'DE-627' => 'K10Plus',
            'DE-101' => 'DNB',
        ];
    }

    /**
     * Returns a list of all clients with title and url
     * @return array
     */
    public function getAllClients()
    {
        $baseDir = '/usr/local/boss';
        $Reader = new Ini();
        $dirs = glob($baseDir . '/local/*', GLOB_ONLYDIR);
        $configs = [];
        foreach ($dirs as $dir) {
            try {
                $config = $Reader->fromFile($dir . '/config/vufind/config.ini');
                $configs[] = [
                    'title' => $config['Site']['title'],
                    'url' => $config['Site']['url']
                ];
            } catch (Exception $ex) {
                continue;
            }
        }

        return $configs;
    }

    /**
     * Reads BOSS2 version number from global config
     * @return string
     */
    public function getVersion()
    {
        $version = $this->get('System')->get('version');
        if (strlen($version) > 0) {
            return $version;
        } else {
            return 'BOSS 3';
        }
    }

    /**
     * Returns Help Regular Expression
     * @return string
     */
    public function getHelpRegEx()
    {
        return $this->get('Help')->get('regex');
    }

    /**
     * Returns Help URL
     * @return string
     */
    public function getHelpUrl()
    {
        return $this->get('Help')->get('url');
    }

    /**
     * Returns Help Groups
     * @return string
     */
    public function getHelpGroups()
    {
        return $this->get('Help')->get('groups');
    }

    /**
     * @return null|Library
     */
    public function getLibrary()
    {
        $library = null;
        if ($this->libraries instanceof Libraries) {
            $library = $this->libraries->getFirstActive($this->getIsils());
        }
        return $library;
    }

    public function getMaintenanceMessage()
    {
        if (defined('MAINTENANCE_MODE')) {
            return getenv('MAINTENANCE_MODE');
        }
        return '';
    }

    public function getOverrideText() {
        $record = $this->get('Record');
        return $record ? ($record->get('override_text') ?? null) : null;
    }
}
