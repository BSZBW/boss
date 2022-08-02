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

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\ServiceManager\ServiceLocatorInterface;

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

    /**
     * @param ServiceLocatorInterface $sm
     *
     * @throws \Exception
     */
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
            glob($this->basepath . '/*'),
            'is_dir'
        );
    }

    /**
     * Show cache folder sizes
     */
    public function showAction()
    {
        $size = 0;
        foreach ($this->localdirs as $local) {
            $size += static::foldersize($local);
        }
        $size /= 1024*1024;
        print "Cache dir size ".number_format($size, 1)."M\n";
    }

    /**
     * Clean the cache     *
     */
    public function cleanAction()
    {
        $count = 0;
        foreach ($this->localdirs as $local) {
            $subdirs = array_filter(glob($local . '/*'), 'is_dir');
            foreach ($subdirs as $sub) {
                $subsub = array_filter(glob($sub . '/*'), 'is_dir');
                foreach ($subsub as $s) {
                    try {
                        static::delete_files($s);
                        $count++;
                    } catch (\Excaption $e) {
                        print "error: could not delete ".$s.". Check permissions.";
                    }
                }
            }
        }
        if ($count > 0) {
            print "Deleted BOSS caches\n";
        } else {
            print "Nothing to do\n";
        }
    }

    /**
     * Delete files and folders recursive
     *
     * @param $target
     */
    private static function delete_files($target)
    {
        if (!is_writable($target)) {
            throw new \Bsz\Exception("File ".$target." is not writable");
        }
        if (is_dir($target)) {
            //GLOB_MARK adds a slash to directories returned
            $files = glob($target . '*', GLOB_MARK);

            foreach ($files as $file) {
                static::delete_files($file);
            }

            rmdir($target);
        } elseif (is_file($target)) {
            unlink($target);
        }
    }

    private function foldersize ($dir)
    {
        $size = 0;

        foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : static::foldersize($each);
        }

        return $size;
    }
}
