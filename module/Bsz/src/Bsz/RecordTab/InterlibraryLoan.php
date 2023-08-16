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

use Bsz\Config\Library;
use Bsz\ILL\Logic;
use Exception;
use VuFind\RecordTab\AbstractBase;

class InterlibraryLoan extends AbstractBase
{
    /**
     * @var Logic
     */
    protected $logic;

    /**
     * @var array
     */
    protected $library;

    /**
     * InterlibraryLoan constructor.
     * @param Logic $logic
     * @param Library $libraries
     * @param bool $active
     * @param bool $internal Link to Dienstoberfläche
     */
    public function __construct(
        Logic $logic,
        Library $library = null,
        bool $active = true,
        bool $internal = false,
        string $orderid = ''
    ) {
        $this->logic = $logic;
        $this->library = $library;
        $this->active = $active;
        $this->internalill = $internal;
        $this->orderid = $orderid;

        $this->accessPermission = 'access.InterlibraryLoanTab';
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Interlibrary Loan';
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isActive()
    {
        if ($this->active) {
            return parent::isActive();
        }
        return false;
    }

    /**
     * @return array
     */
    public function getContent()
    {
        $this->logic->attachDriver($this->driver);

        $customUrl = false;
        $verbund = $this->driver->getNetwork();
        $titelid = $this->driver->getPPN();

        // workaround for HBZ titles
        if ($verbund == 'HBZ') {
            $titelid = $this->driver->getUniqueId();
            $titelid = preg_replace('/\(.*\)/', '', $titelid);
        }

        if ($this->internalill) {
            $query = http_build_query([
                'titelid' => $titelid,
                'verbund' => $verbund,
                'bestellid' => $this->orderid ?? ''
            ]);
            $url = 'https://%s.bsz-bw.de/flcgi/fernleihe_boss.pl?'.$query;
            if (getenv('VUFIND_ENV') === 'production') {
                $customUrl = sprintf($url, 'zfl');
            } else {
                $customUrl = sprintf($url, 'fltest');
            }
        } elseif ($this->library && $this->library->hasCustomUrl()) {
            $customUrl = $this->library->getCustomUrl();
        }
        return [
            'status' => $this->logic->isAvailable(),
            'messages' => $this->logic->getMessages(),
            'ppns' => $this->logic->getPPNs(),
            'linklabels' => $this->logic->getLinkLabels(),
            'library' => $this->library,
            'customUrl' => $customUrl
        ];
    }
}
