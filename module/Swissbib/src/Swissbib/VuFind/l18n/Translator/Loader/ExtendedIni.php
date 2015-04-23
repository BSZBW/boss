<?php
/**
 * VuFind Translate Adapter ExtendedIni
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  Translator
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace Swissbib\VuFind\l18n\Translator\Loader;
use Zend\I18n\Exception\InvalidArgumentException,
    Zend\I18n\Translator\Loader\FileLoaderInterface,
    Zend\I18n\Translator\TextDomain,
    VuFind\I18n\Translator\Loader\ExtendedIni as VFExtendedIni;



/**
 * Handles the language loading and language file parsing
 *
 * @category VuFind2
 * @package  Translator
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class ExtendedIni extends VFExtendedIni
{
    /**
     * List of files loaded during the current run -- avoids infinite loops and
     * duplicate loading.
     *
     * @var array
     */
    protected $loadedFiles = array();

    /**
     * Constructor
     *
     * @param array  $pathStack      List of directories to search for language
     * files.
     * @param string $fallbackLocale Fallback locale to use for language strings
     * missing from selected file.
     */
    public function __construct($pathStack = array(), $fallbackLocale = null)
    {

        parent::__construct($pathStack, $fallbackLocale);

    }

    /**
     * load(): defined by LoaderInterface.
     *
     * @param string $locale   Locale to read from language file
     * @param string $filename Language file to read (not used)
     *
     * @return TextDomain
     * @throws InvalidArgumentException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function load($locale, $filename)
    {
        // Load base data:
        //VuFind itself doesn't use at all the filename information itself
        //we are running into problems with domain entities having the same name but being part of different domains
        //specialized domains are registered in Swissbib\Bootstraper->initSpecialTranslations
        //todo: discuss this with VuFind list! we sent already a pull request but still open

        // Reset the loaded files list:
        $this->resetLoadedFiles();

        // Load base data:
        $data = (!isset($filename))
            ? $this->loadLanguageFile($locale . '.ini')
            : $this->loadLanguageFile($filename);

        //do we need the fallbacks in multi domain environment for translations?
        // Load fallback data, if any:
        /*
        if (!empty($this->fallbackLocales)) {
            foreach ($this->fallbackLocales as $fallbackLocale) {
                $newData = $this->loadLanguageFile($fallbackLocale . '.ini');
                $newData->merge($data);
                $data = $newData;
            }
        }
        */

        return $data;
    }



    /**
     * Search the path stack for language files and merge them together.
     *
     * @param string $filename Name of file to search path stack for.
     *
     * @return TextDomain
     */
    protected function loadLanguageFile($filename)
    {

        // Don't load a file that has already been loaded:
        if ($this->checkAndMarkLoadedFile($filename)) {
            return new TextDomain();
        }

        $data = false;
        $matchesLocation = [];
        if (file_exists($filename)) {

            //is the case with native.ini translations
            $data = $this->reader->getTextDomain($filename);
        } elseif (preg_match('/(.*?-.*?)\\.ini/',$filename,$matchesLocation)) {

            //GH: this is a big hack to fetch the multi domain location translations
            //compare comment in config_base.ini
            $wholeFileName = array_filter($matchesLocation, function ($arrValue) {
                return preg_match("#ini#",$arrValue);
            });

            if (is_array($wholeFileName) && count($wholeFileName) == 1)
            {
                foreach ($this->pathStack as $path) {

                    if (preg_match('/location$/', $path)) {

                        $fullFilePath = $path . '/' . $wholeFileName[0];

                        if ($fullFilePath && file_exists($fullFilePath)) {

                            $current = $this->reader->getTextDomain($fullFilePath);
                            if ($data === false) {
                                $data = $current;
                            } else {
                                $data->merge($current);
                            }
                        }
                    }

                }

            }

        } else {

            //simple VuFind translation
            //we have to collect the translation files in APP_BASE/languages and
            //APP_BASE/locale/languages
            if (!preg_match('/\//',$filename))
            {
                foreach ($this->pathStack as $path) {

                    if (preg_match('/languages$/', $path)) {

                        $fullFilePath = $path . '/' . $filename;

                        if ($fullFilePath && file_exists($fullFilePath)) {

                            $current = $this->reader->getTextDomain($fullFilePath);
                            if ($data === false) {
                                $data = $current;
                            } else {
                                $data->merge($current);
                            }
                        }
                    }

                }
            } else {
                //we are dealing with a specialized domain
                //if we are dealing with a multi domain translation the filename contains a slash character
                //compare the initialization method initSpecialTranslations in Bootstrapper
                $matches = [];
                preg_match('/(.*?)\\//',$filename,$matches);
                foreach ($this->pathStack as $path) {

                    $found = array_filter($matches, function ($arrValue) use ($path) {
                        return preg_match("#{$arrValue}#",$path);
                    });
                    if (count($found) > 0) {
                        $test = strrpos($filename,"/");
                        $fileNameWithoutPath = $test ? substr($filename,$test + 1) : $filename;
                        //preg_match('//',$filename,$matches);
                        $fullFilePath = $path . '/' . $fileNameWithoutPath;
                        if ($fullFilePath && file_exists($fullFilePath)) {

                            $current = $this->reader->getTextDomain($fullFilePath);
                            if ($data === false) {
                                $data = $current;
                            } else {
                                $data->merge($current);
                            }
                        }

                    }

                }
            }
        }

        if ($data === false) {
            throw new InvalidArgumentException("Ini file '{$filename}' not found");
        }

        // Load parent data, if necessary:
        return $this->loadParentData($data);


    }

}
