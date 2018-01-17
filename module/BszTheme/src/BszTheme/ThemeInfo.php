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

namespace BszTheme;

/**
 * BSZ implementation of ThemeInfo, here we load all client specific ressources. 
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class ThemeInfo extends \VuFindTheme\ThemeInfo {
    
    protected $tag;
    
    /**
     * Adapted constructor
     * @param string $baseDir
     * @param string $safeTheme
     * @param \Bsz\Config\Client $Client
     */
    
    public function __construct($baseDir, $safeTheme, $tag)
    {
        $this->baseDir = $baseDir;
        $this->currentTheme = $this->safeTheme = $safeTheme;
        $this->tag = $tag;
    }
    
    /**
     * Get all the configuration details related to the current theme.
     *
     * @return array
     */
    public function getThemeInfo()
    {
        // Fill in the theme info cache if it is not already populated:
        if (null === $this->allThemeInfo) {
            // Build an array of theme information by inheriting up the theme tree:
            $this->allThemeInfo = [];
            $currentTheme = $this->getTheme();
            do {
                $this->allThemeInfo[$currentTheme]
                    = include $this->getThemeConfig($currentTheme);

                
                
                $currentTheme = $this->allThemeInfo[$currentTheme]['extends'];
            } while ($currentTheme);
            
            // Here, we make the css files dynamic
            $first = array_keys($this->allThemeInfo)[0];
            $second = array_keys($this->allThemeInfo)[1];
            $this->allThemeInfo[$first]['favicon'] = $this->addClientFavicon();
            
            $css = isset($this->allThemeInfo[$first]['css']) ? $this->allThemeInfo[$first]['css'] : [];   
            array_push($css, $this->addClientStylesheet());
            $this->allThemeInfo[$first]['css'] = $css;   
            
            // we then remove the compiled.css because it's included in our dynamic version 
            if (isset($this->allThemeInfo[$second]['css'])) {
                foreach ($this->allThemeInfo[$second]['css'] as $key => $value) {
                    if ($value == 'compiled.css') {
                        unset($this->allThemeInfo[$second]['css'][$key]);
                    }
                }
            }
        }
        return $this->allThemeInfo;
    }
    
    /**
     * 
     * @return string
     */
    public function addClientStylesheet() {
        return $this->tag.'.css';
    }    
    /**
     * 
     * @return string
     */
    public function addClientFavicon() {
        return 'favicon/'.$this->tag.'.ico';
    }
}

