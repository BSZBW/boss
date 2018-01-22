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
namespace Bsz\Cover;
/**
 * This class fixes some issues of the original VuFind Generator class. 
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Generator extends \VuFind\Cover\Generator {
    
    /**
     * Constructor
     * @param \VuFindTheme\ThemeInfo $themeInfo
     * @param array $settings
     */
    public function __construct(\VuFindTheme\ThemeInfo $themeInfo, $settings = []) {
        parent::__construct($themeInfo, $settings);
    }
    
    /**
     * Generates a dynamic cover image from elements of the book
     *
     * @param string $title      Title of the book
     * @param string $author     Author of the book
     * @param string $callnumber Callnumber of the book
     *
     * @return string contents of image file
     */
    public function generate($title, $author, $callnumber = null)
    {
        if ($this->settings->mode == 'solid') {
            return $this->generateSolid($title, $author, $callnumber);
        } 
        else if($this->settings->mode == 'grid') {
            return $this->generateGrid($title, $author, $callnumber);
        }
        //Set to html or false
        else {            
            return false;
        }
        
        
    }
}