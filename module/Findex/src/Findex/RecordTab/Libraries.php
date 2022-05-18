<?php
/*
 * Copyright 2022 (C) Bibliotheksservice-Zentrum Baden-
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

namespace Findex\RecordTab;

use Bsz\Config\Library;
use Bsz\RecordTab\Libraries as BszLibrariesTab;

class Libraries extends BszLibrariesTab
{

    /**
     * @return array|mixed
     */
    public function getContent()
    {
        if (null === $this->content) {
            $this->content = $this->driver->tryMethod('getField980');
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
    /**
     * Tab is shown if there is at least one 924 in MARC.
     * @return boolean
     */
    public function isActive()
    {
        // If accessPermission is set, check for authorization to enable tab
        $parent = true;

        if (!empty($this->accessPermission)) {
            $auth = $this->getAuthorizationService();
            if (!$auth) {
                throw new \Exception('Authorization service missing');
            }
            $parent =  $auth->isGranted($this->accessPermission);
        }

        if (null === $this->content) {
            $this->content = $this->driver->tryMethod('getField980');
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

}