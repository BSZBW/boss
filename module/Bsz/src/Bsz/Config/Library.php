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

use Laminas\Db\ResultSet\ResultSet;

/**
 * Simple Library Object - uses for Interlending view
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Library
{
    /**
     * Used if no custom url is set
     */
    const DAIA_DEFAULT_URL = 'https://daia.ibs-bw.de/isil/%s';

    protected $name;
    protected $isil;
    protected $sigel;
    protected $homepage;
    protected $email;
    protected $auth;
    protected $places = null;
    protected $daia;
    protected $openurl;
    protected $opacurl;
    protected $idp;
    protected $logout;
    protected $regex;
    protected $live;
    protected $boss;
    protected $lend_copy;
    protected $country;

    /**
     * @param $data
     */
    public function exchangeArray($data)
    {
        $this->name = $data['name'];
        $this->isil = $data['isil'];
        $this->sigel = $data['sigel'];
        $this->live = (bool)$data['is_live'];
        $this->boss = (bool)$data['is_boss'];
        $this->homepage = $data['homepage'];
        $this->isil_availability = $data['isil_availability'];
        $this->email = $data['email'];
        $this->auth = $data['auth_name'] ?? 'adis';
        $this->auth2 = $data['auth2_name'] ?? null;
        $this->daia = $data['daiaurl'] ?? null;
        $this->openurl = $data['openurl'] ?? null;
        $this->opacurl = $data['opacurl'] ?? null;
        $this->idp = $data['shibboleth_idp'] ?? null;
        $this->logout = $data['shibboleth_logout'] ?? null;
        $this->regex = $data['regex'] ?? null;
        $this->lend_copy = isset($data['lend_copy']) ? str_split($data['lend_copy'], 1) : [0b1, 0b1];
    }

    /**
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }

    /**
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return string
     */
    public function getIsil()
    {
        return $this->isil;
    }

    /**
     *
     * @return string
     */
    public function getSigel()
    {
        return $this->sigel;
    }

    /**
     * Get places to collect items
     * @return array
     */
    public function getPlaces()
    {
        return $this->places;
    }

    /**
     * Get authentication method, adis is default
     * @return array
     */
    public function getAuth()
    {
        $auths = [$this->auth, $this->auth2];
        return array_filter($auths, static function ($var) {
            return $var !== null;
        });
        ;
    }

    /**
     *
     * @return boolean
     */
    public function hasPlaces() : bool
    {
        if (!empty($this->places)) {
            return true;
        }
        return false;
    }

    /**
     * Returns DAIA  URL
     * @return string
     */
    public function getUrlDaia()
    {
        if (isset($this->daia)) {
            return $this->daia;
        } else {
            return static::DAIA_DEFAULT_URL;
        }
    }

    /**
     * Determine if this library uses the productive ill link or the dev one.
     * @return boolean
     */
    public function isLive() : bool
    {
        if (isset($this->live) && $this->live === true) {
            return true;
        }
        return false;
    }

    public function isBoss() : bool
    {
        if (isset($this->boss) && $this->boss === true) {
            return true;
        }
        return false;
    }

    /**
     * Does this library have a custom URL for ILL form?
     * @return boolean
     */
    public function hasCustomUrl()
    {
        if (isset($this->openurl) && strlen($this->openurl) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Get custom URL for ill form
     * @return string
     */
    public function getCustomUrl()
    {
        if ($this->hasCustomUrl()) {
            return $this->openurl;
        }
        return '';
    }

    /**
     *
     * @return array
     */
    public function getIsilAvailability()
    {
        $isils = [];
        if (isset($this->isil_availability)) {
            $raw = $this->isil_availability;
            if (!empty($raw)) {
                $isils = explode(',', $raw);
            }
        }
        $isils[] = $this->getIsil();
        return array_unique($isils);
    }

    /**
     *
     * @param ResultSet $places
     * @return Library
     */
    public function setPlaces($places)
    {
        $this->places = $places;
        return $this;
    }

    /**
     * Returns homepage
     * @return string
     */
    public function getHomepage()
    {
        return $this->homepage;
    }

    /**
     * Returns library logo
     * @return string
     */
    public function getLogo()
    {
        $sigel = str_replace(' ', '', $this->getSigel());
        $sigel = preg_replace('/\/.*/', '', $sigel);
        return 'logo/libraries/' . $sigel . '.jpg';
    }

    /**
     * Get Url to OPAC, with placeholder for PPN.
     *
     * @return string|url
     */
    public function getOpacUrl()
    {
        return $this->opacurl;
    }

    /**
     * Get Shibboleth IdP
     * @return string
     */
    public function getIdp()
    {
        return $this->idp;
    }

    /**
     * Get the special logout URL for SLO if it's
     * @return string
     */
    public function getLogoutUrl()
    {
        return $this->logout;
    }

    /**
     * Get library-specific regex to trim username
     * @return string
     */
    public function getRegex()
    {
        return (string)$this->regex;
    }

    /**
     * @return bool
     */
    public function allowsLend()
    {
        return $this->lend_copy[0] == 0b1;
    }

    /**
     * @return bool
     */
    public function allowsCopy()
    {
        return $this->lend_copy[1] == 0b1;
    }

    /**
     * @return bool
     */
    public function loginEnabled()
    {
        $auth = $this->getAuth();
        if (in_array('shibboleth', $auth)
            || in_array('kohaauth', $auth)
            || in_array('ncip', $auth)
        ) {
            return true;
        }
        return false;
    }

    /**
     * @return array
     */
    public function getFirstAuth()
    {
        $auths = $this->getAuth();
        return array_shift($auths);
    }
}
