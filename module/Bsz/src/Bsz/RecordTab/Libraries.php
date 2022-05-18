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
namespace Bsz\RecordTab;

use Bsz\Config\Libraries as LibConf;
use Bsz\Config\Library;
use VuFind\RecordTab\AbstractBase;

/**
 * Description of Libraries
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Libraries extends AbstractBase
{
    /**
     * @var LibConf
     */
    protected $libraries;
    /**
     * @var array
     */
    protected $content;
    /**
     * @var bool
     */
    protected $visible;
    /**
     * @var bool
     */
    protected $swbonly;

    public function __construct(LibConf $libraries, $visible = true, $swbonly = false)
    {
        $this->accessPermission = 'access.LibrariesViewTab';
        $this->libraries = $libraries;
        $this->visible = (bool)$visible;
        $this->swbonly = $swbonly;
    }

    public function getDescription()
    {
        return 'Libraries';
    }

    /**
     * Tab is shown if there is at least one 924 in MARC.
     * @return boolean
     */
    public function isActive()
    {
        $parent = parent::isActive();
        if (null === $this->content) {
            $this->content = $this->driver->tryMethod('getField924');
        }
        if ($this->swbonly) {
            foreach ($this->content as $k => $field) {
                if (isset($field['region']) && strtoupper($field['region']) !== 'BSZ') {
                    unset($this->content[$k]);
                }
            }
        }
        if ($parent && $this->content) {
            return true;
        }
        return false;
    }

    public function getContent()
    {
        if (null === $this->content) {
            $this->content = $this->driver->tryMethod('getField924');
        }
        if (is_array($this->content)) {
            foreach ($this->content as $k => $f924) {
                $library = $this->libraries->getByIsil($f924['isil']);
                if ($library instanceof Library) {
                    $this->content[$k]['name'] = $library->getName();
                    $this->content[$k]['opacurl'] = $library->getOpacUrl();
                    $this->content[$k]['homepage'] = $library->getHomepage();
                }
            }
        }
        return $this->content;
    }

    public function isVisible()
    {
        return $this->visible;
    }
}
