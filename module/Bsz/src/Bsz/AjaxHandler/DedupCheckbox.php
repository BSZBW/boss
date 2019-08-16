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
namespace Bsz\AjaxHandler;

use VuFind\I18n\Translator\TranslatorAwareInterface,
    Zend\Mvc\Controller\Plugin\Params,
    Bsz\Config\Dedup;

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
class DedupCheckbox extends \VuFind\AjaxHandler\AbstractBase implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     *
     * @var Bsz\Config\Dedup
     */
    protected $dedup;

    /**
     * Constructor
     *
     * @param Bsz\Config\Dedup  $dedup
     */
    public function __construct(Dedup $dedup
    ) {
        $this->dedup = $dedup;
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
        $status = $params->fromPost('status');
        $status = $status == 'true' ? true : false;
        $this->dedup->store(['group' => $status]); 
        return $this->formatResponse([], 200);    
    }
}
