<?php
/**
 * "Get Resolver Links" AJAX handler
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2018.
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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Graham Seaman <Graham.Seaman@rhul.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
namespace VuFind\AjaxHandler;

use VuFind\I18n\Translator\TranslatorAwareInterface;
use VuFind\Resolver\Connection;
use VuFind\Resolver\Driver\PluginManager as ResolverManager;
use VuFind\Session\Settings as SessionSettings;
use Zend\Config\Config;
use Zend\Mvc\Controller\Plugin\Params;
use Zend\View\Renderer\RendererInterface;

/**
 * "Get Resolver Links" AJAX handler
 *
 * Fetch Links from resolver given an OpenURL and format as HTML
 * and output the HTML content in JSON object.
 *
 * @category VuFind
 * @package  AJAX
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Graham Seaman <Graham.Seaman@rhul.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development Wiki
 */
class DedupCheckbox extends AbstractBase implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * Resolver driver plugin manager
     *
     * @var ResolverManager
     */
    protected $pluginManager;

    /**
     * View renderer
     *
     * @var RendererInterface
     */
    protected $renderer;

    /**
     * Top-level VuFind configuration (config.ini)
     *
     * @var Config
     */
    protected $config;

    /**
     * Constructor
     *
     * @param SessionSettings   $ss       Session settings
     * @param ResolverManager   $pm       Resolver driver plugin manager
     * @param RendererInterface $renderer View renderer
     * @param Config            $config   Top-level VuFind configuration (config.ini)
     */
    public function __construct(SessionSettings $ss, ResolverManager $pm,
        RendererInterface $renderer, Config $config
    ) {
        $this->sessionSettings = $ss;
        $this->pluginManager = $pm;
        $this->renderer = $renderer;
        $this->config = $config;
    }

    /**
     * Handle a request.
     *
     * @param Params $params Parameter helper from controller
     *
     * @return array [response data, HTTP status code]
     */
    public function handleRequest(Params $params)
    {
        // $status = $this->params()->fromPost('status');
        $status = $status == 'true' ? true : false;
        $dedup = $this->get('Bsz/Config/Dedup');
        $dedup->store(['group' => $status]); 
        return $this->output([], self::STATUS_OK);    
    }
}
