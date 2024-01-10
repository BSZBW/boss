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

use Bsz\Config\Client;
use Bsz\Config\Library;
use Bsz\RecordTab\Libraries as BszLibrariesTab;

class FindexLibraries extends BszLibrariesTab
{
    protected Client $client;

    public function __construct(\Bsz\Config\Libraries $libraries, Client $client)
    {
        parent::__construct(
            $libraries,
            !$client->is('disable_library_tab'),
            $client->getTag() === 'swb' ?? false
        );
        $this->client = $client;
    }

    protected $content = null;
    /**
     * @return array|mixed
     */
    public function getContent()
    {
        if (null === $this->content) {
            $this->content = [];
            $content =  $this->driver->tryMethod('getField980');
            if (is_array($content)) {
                foreach ($content as $k => $f924) {
                    $library = $this->libraries->getByIsil($f924['isil']);
                    if ($library instanceof Library) {
                        $this->content[$k] = $f924;
                        $this->content[$k]['name'] = $library->getName();
                        $this->content[$k]['opacurl'] = $library->getOpacUrl();
                        $this->content[$k]['homepage'] = $library->getHomepage();
                    }
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
        return (!empty($this->getContent()) || $this->client->is('wlb2'));
    }

}