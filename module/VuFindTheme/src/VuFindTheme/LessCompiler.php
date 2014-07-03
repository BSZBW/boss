<?php
/**
 * Class to compile LESS into CSS within a theme.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2014.
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
 * @package  Theme
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
namespace VuFindTheme;

/**
 * Class to compile LESS into CSS within a theme.
 *
 * @category VuFind2
 * @package  Theme
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org   Main Site
 */
class LessCompiler
{
    /**
     * Base path of VuFind.
     *
     * @var string
     */
    protected $basePath;

    /**
     * Fake base path used for generating absolute paths in CSS.
     *
     * @var string
     */
    protected $fakePath = '/zzzz_basepath_zzzz/';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->basePath = realpath(__DIR__ . '/../../../../');
    }

    /**
     * Compile the scripts.
     *
     * @param array $themes Array of themes to process (empty for ALL themes).
     *
     * @return void
     */
    public function compile(array $themes)
    {
        if (empty($themes)) {
            $themes = $this->getAllThemes();
        }

        foreach ($themes as $theme) {
            $this->processTheme($theme);
        }
    }

    /**
     * Compile scripts for the specified theme.
     *
     * @param string $theme Theme name
     *
     * @return void
     */
    protected function processTheme($theme)
    {
        $config = $this->basePath . '/themes/' . $theme . '/theme.config.php';
        if (!file_exists($config)) {
            return;
        }
        $config = include($config);
        if (!isset($config['less'])) {
            return;
        }
        foreach ($config['less'] as $less) {
            $this->compileFile($theme, $less);
        }
    }

    /**
     * Compile a LESS file inside a theme.
     *
     * @param string $theme Theme containing file
     * @param string $less  Relative path to LESS file
     *
     * @return void
     */
    protected function compileFile($theme, $less)
    {
        $directories = array();
        $info = new ThemeInfo($this->basePath . '/themes', $theme);
        foreach (array_keys($info->getThemeInfo()) as $curTheme) {
            $directories["{$this->basePath}/themes/$curTheme/less/"]
                = $this->fakePath . "themes/$curTheme/css/";
        }
        $lessDir = $this->basePath . '/themes/' . $theme . '/less/';
        $outDir = sys_get_temp_dir();
        $outFile = \Less_Cache::Regen(
            array($lessDir . $less => $this->fakePath . "themes/$theme/css/less"),
            array(
                'cache_dir' => $outDir,
                'cache_method' => false,
                'compress' => true,
                'import_dirs' => $directories
            )
        );
        $css = file_get_contents($outDir . '/' . $outFile);
        $finalOutDir = $this->basePath . '/themes/' . $theme . '/css/';
        list($fileName, ) = explode('.', $less);
        $finalFile = $finalOutDir . $fileName . '.css';
        if (!is_dir(dirname($finalFile))) {
            mkdir(dirname($finalFile));
        }
        file_put_contents($finalFile, $this->makeRelative($css, $less));
    }

    /**
     * Convert fake absolute paths to working relative paths.
     *
     * @param string $css  Generated CSS
     * @param string $less Relative LESS filename
     *
     * @return string
     */
    protected function makeRelative($css, $less)
    {
        // Figure out how deep the LESS file is nested -- this will
        // affect our relative path.
        $depth = preg_match_all('|/|', $less, $matches);
        $relPath = '../../../';
        for ($i = 0; $i < $depth; $i++) {
            $relPath .= '/../';
        }
        return str_replace($this->fakePath, $relPath, $css);
    }

    /**
     * Get a list of all available themes.
     *
     * @return array
     */
    protected function getAllThemes()
    {
        $baseDir = $this->basePath . '/themes/';
        $dir = opendir($baseDir);
        $list = array();
        while ($line = readdir($dir)) {
            if (is_dir($baseDir . $line)
                && file_exists($baseDir . $line . '/theme.config.php')
            ) {
                $list[] = $line;
            }
        }
        closedir($dir);
        return $list;
    }
}