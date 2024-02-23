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

namespace BszTheme\View\Helper;

use Bsz\Config\Library;
use Laminas\View\Helper\AbstractHelper;

/**
 * This view helper is used to provide client specific assets, like images.
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class ClientAsset extends AbstractHelper
{
    protected $tag;

    protected $isil = null;

    protected $library;

    protected $website;

    /**
     * The first part of the domain name
     *
     * @param string $tag
     * @param $website
     * @param null $library
     */
    public function __construct($tag, $website, $library = null)
    {
        $this->tag = $tag;
        $this->library = $library;
        $this->website = $website;
    }


    /**
     * @return $this
     */
    public function __invoke()
    {
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getHeader()
    {
        return 'header/' . $this->tag . '.jpg';
    }

    /**
     *
     * @return string
     */
    public function getSmallLogo()
    {
        $filename = '';
        if ($this->library === null) {
            $filename = 'logo/' . $this->tag . '-small.png';
        } elseif ($this->library instanceof Library) {
            $filename = $this->library->getLogo();
        }
        return $filename;
    }

    /**
     * @param bool $bwms BW Music Search
     *
     * @return string
     */
    public function getLogo($bwms = false)
    {
        $filename = '';
        if ($this->library === null && ($this->tag === 'swb' || $this->tag === 'k10plus')) {
            $filename = 'logo/' . $this->tag . '.svg';
        } elseif ($bwms) {
            $filename = 'logo/bwms_' . $this->tag . '_desktop.svg';
        } elseif ($this->library === null) {
            $filename = 'logo/' . $this->tag . '.png';
        } elseif ($this->library instanceof Library) {
            $filename = $this->library->getLogo();
        }
        return $filename;
    }


    /**
     * @param bool $bwms BW Music Search Logo
     *
     * @return string
     */
    public function getLogoHtml($bwms = false): string
    {
        return $this->getView()->render('bsz/logo.phtml', [
            'website' => $this->website,
            'imglink' => $this->getLogo($bwms),
            'alt' => $bwms ? 'Logo BW Music Search' : 'Logo Bibliothek'
        ]);
    }

}
