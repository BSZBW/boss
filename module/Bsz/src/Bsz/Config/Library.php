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

namespace Bsz\Config;
use Zend\Db\ResultSet\ResultSet;


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
    protected $adisurl;
    protected $idp;
    protected $regex;
 

    public function exchangeArray($data)
    {
        $this->name = $data['name'];
        $this->isil = $data['isil'];
        $this->sigel = $data['sigel'];
        $this->live = (bool)$data['is_live'];
        $this->homepage = $data['homepage'];
        $this->isil_availability = $data['isil_availability'];
        $this->email = $data['email'];
        $this->auth = isset($data['auth_name']) ? $data['auth_name'] : 'adis';
        $this->daia = isset($data['daiaurl']) ? $data['daiaurl'] : null;
        $this->openurl = isset($data['openurl']) ? $data['openurl'] : null;
        $this->adisurl = isset($data['adisurl']) ? $data['adisurl'] : null;        
        $this->idp = isset($data['shibboleth_idp']) ? $data['shibboleth_idp'] : null;        
        $this->regex = isset($data['regex']) ? $data['regex'] : null;
        
        
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
     * @return stringl
     */
    public function getAuth()
    {
        return $this->auth;
        
    }

    /**
     * 
     * @return int
     */
    public function getCountry()
    {
        return (int) $this->country;
    }
    
    /**
     * 
     * @return boolean
     */
    public function hasPlaces()
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
    public function getURLDAIA()
    {
        if(isset($this->daia)) {
            return $this->daia;            
        } else {
            return static::DAIA_DEFAULT_URL;
        }
    }
    
    /**
     * Determine if this library uses the productive ill link or the dev one. 
     * @return boolean
     */
    public function isLive() 
    {
        if (isset($this->live) && $this->live === true) {
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
     * @return \Bsz\Config\Library
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
    public function getLogo() {
        $sigel = str_replace(' ', '', $this->getSigel());
        return 'logo/libraries/'.$sigel.'.jpg';
    }
    
    /**
     * Get aDIS URL
     * 
     * @return string|url
     */
    public function getaDisUrl() 
    {
        return $this->adisurl;
    }
    
    /**
     * Get Shiboleth IdP
     * 
     * @return string|url
     */
    public function getIdp() 
    {
        return $this->idp;
    }
    
    /**
     * Get library-specific regex to trim username
     * 
     * @return string
     */
    public function getRegex() 
    {
        return (string)$this->regex;
    }
    

}
