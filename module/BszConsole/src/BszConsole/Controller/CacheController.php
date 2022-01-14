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

namespace BszConsole\Controller;

use Zend\Console\Console;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class CacheController
 * @package  BszConsole\Controller
 * @category boss
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class CacheController extends AbstractActionController
{
    protected $basepath;
    protected $localdirs;

    public function __construct(ServiceLocatorInterface $sm)
    {
        // This controller should only be accessed from the command line!
        if (PHP_SAPI != 'cli') {
            throw new \Exception('Access denied to command line tools.');
        }
        $this->serviceLocator = $sm;

        $this->basepath = defined(LOCAL_CACHE_DIR)
            ? getenv('LOCAL_CACHE_DIR')
            : '/data/boss/cache';
        $this->localdirs = array_filter(
            glob($this->basepath.'/*'), 'is_dir'
        );
    }
    public function showAction()
    {
        $output = [];

        foreach ($this->localdirs as $local) {
            exec('du -sh '.$LOCAL, $output);
            print $output[0];
        }

    }
}