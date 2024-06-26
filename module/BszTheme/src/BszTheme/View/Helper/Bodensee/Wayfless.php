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

namespace BszTheme\View\Helper\Bodensee;

use Laminas\View\Helper\AbstractHelper;

class Wayfless extends AbstractHelper
{
    protected $cfg;
    protected $libtag;

    public function __construct(\Bsz\Config\Client $cfg, $libtag)
    {
        $this->cfg = $cfg;
        $this->libtag = $libtag;
    }


    /**
     * @param string $link
     *
     * @return string
     */
    public function __invoke(string $link) : string
    {
        $return = $link;

        if ($this->cfg->offsetExists($this->libtag)) {
            foreach ($this->cfg->get($this->libtag) as $raw) {
                list($search, $replace) = explode('#', $raw);
                if ($this->cfg->get('regex') && preg_match('/'.$search.'/i', $link)) {
                    $return = preg_replace('/'.$search.'/i', $replace, $link);
                } elseif(strpos($link, $search) !== FALSE) {
                    $return = str_replace($search, $replace, $link)        ;
                }
            }
        }
        return $return;
    }
}