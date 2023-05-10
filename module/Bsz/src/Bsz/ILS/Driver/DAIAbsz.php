<?php
/**
 * ILS Driver for VuFind to query availability information via DAIA.
 * Based on the proof-of-concept-driver by Till Kinstler, GBV.
 * Relaunch of the daia driver developed by Oliver Goldschmidt.
 * PHP version 5
 * Copyright (C) Jochen Lienhard 2014.
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * @category VuFind2
 * @package  ILS_Drivers
 * @author   Jochen Lienhard <lienhard@ub.uni-freiburg.de>
 * @author   Oliver Goldschmidt <o.goldschmidt@tu-harburg.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */

namespace Bsz\ILS\Driver;

use Exception;
use VuFind\Exception\ILS as ILSException;

/**
 * ILS Driver for VuFind to query availability information via DAIA.
 * @category VuFind2
 * @package  ILS_Drivers
 * @author   Jochen Lienhard <lienhard@ub.uni-freiburg.de>
 * @author   Oliver Goldschmidt <o.goldschmidt@tu-harburg.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
class DAIAbsz extends \VuFind\ILS\Driver\DAIA
{
    use ItemTrait;

    protected $isil;
    protected $parsePpn = true;
    /**
     * Here, we store our holdings.
     * @var array
     */
    protected $holdings = [];

    /**
     * Flag to enable multiple DAIA-queries
     * @var bool
     */
    protected $multiQuery = false;

    public function __construct(\VuFind\Date\Converter $converter, $isil, $baseUrl = '')
    {
        $this->dateConverter = $converter;
        $this->isil = $isil;
        if (strlen($baseUrl) > 0) {
            $this->baseUrl = $baseUrl;
        }
    }

    /**
     * Initialize the driver.
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     * @return void
     * @throws ILSException
     */
    public function init()
    {
        if (isset($this->config['DAIA']['baseUrl']) && !isset($this->baseUrl)) {
            $this->baseUrl = $this->config['DAIA']['baseUrl'];
        } elseif (isset($this->config['Global']['baseUrl'])) {
            throw new ILSException(
                'Deprecated [Global] section in DAIA.ini present, but no [DAIA] ' .
                'section found: please update DAIA.ini (cf. config/vufind/DAIA.ini).'
            );
        } /* do not throw an exception, as we need to switch off DAIA in ill portal
         * else {
            throw new ILSException('DAIA/baseUrl configuration needs to be set.');
        }*/
        if (isset($this->isil) && strpos($this->baseUrl, '%s') !== false) {
            $this->baseUrl = sprintf($this->baseUrl, array_shift($this->isil));
        }
        if (isset($this->config['DAIA']['daiaResponseFormat'])) {
            $this->daiaResponseFormat = strtolower(
                $this->config['DAIA']['daiaResponseFormat']
            );
        } else {
            $this->debug('No daiaResponseFormat setting found, using default: xml');
            $this->daiaResponseFormat = 'xml';
        }
        if (isset($this->config['DAIA']['daiaIdPrefix'])) {
            $this->daiaIdPrefix = $this->config['DAIA']['daiaIdPrefix'];
        } else {
            $this->debug('No daiaIdPrefix setting found, using default: ppn:');
            $this->daiaIdPrefix = 'ppn:';
        }
        if (isset($this->config['DAIA']['multiQuery'])) {
            $this->multiQuery = $this->config['DAIA']['multiQuery'];
        } else {
            $this->debug('No multiQuery setting found, using default: false');
        }
        if (isset($this->config['DAIA']['daiaContentTypes'])) {
            $this->contentTypesResponse = $this->config['DAIA']['daiaContentTypes'];
        } else {
            $this->debug('No ContentTypes for response defined. Accepting any.');
        }
    }

    /**
     * Get Hold Link
     * The goal for this method is to return a URL to a "place hold" web page on
     * the ILS OPAC. This is used for ILSs that do not support an API or method
     * to place Holds.
     * Uses the mobile version of aDIS by exchanging a number
     *
     * @param string $id     The id of the bib record
     * @param array $details Item details from getHoldings return array
     *
     * @return string         URL to ILS's OPAC's place hold screen.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getHoldLink($id, $details)
    {
        $link = null;
        if (isset($details['ilslink']) && $details['ilslink'] != '') {
            $link = str_replace('2&sp=', '8&sp=', $details['ilslink']);
            $details['ilslink'] = $link;
        }
        return $details['ilslink'];
    }

    public function translationEnabled()
    {
        if (isset($this->config['DAIA']['noTranslation'])) {
            return false;
        }
        return true;
    }

    /**
     * Needed to hide holdings tab if empty
     *
     * @param string $id
     *
     * @return boolean
     */
    public function hasHoldings($id)
    {
        // we can't query DAIA without an ISIL.
        if (empty($this->isil) && strpos($this->baseUrl, 'DE-') === false) {
            return false;
        }
        $holdings = $this->getHolding($id);
        if (count($holdings) > 0) {
            return true;
        } else {
            throw new ILSException('no holdings');
        }
        return false;
    }

    /**
     * Parse an array with DAIA status information.
     *
     * @param string $id       Record id for the DAIA array.
     * @param array $daiaArray Array with raw DAIA status information.
     *
     * @return array            Array with VuFind compatible status information.
     */
    protected function parseDaiaArray($id, $daiaArray)
    {
        $doc_id = null;
        $doc_href = null;
        $result = [];

        if (array_key_exists('id', $daiaArray)) {
            $doc_id = $daiaArray['id'];
        }
        if (array_key_exists('href', $daiaArray)) {
            // url of the document (not needed for VuFind)
            $doc_href = $daiaArray['href'];
        }
        if (array_key_exists('message', $daiaArray)) {
            // log messages for debugging
            $this->logMessages($daiaArray['message'], 'document');
        }
        // if one or more items exist, iterate and build result-item
        if (array_key_exists('item', $daiaArray)) {
            $number = 0;
            foreach ($daiaArray['item'] as $item) {
                $result_item = [];
                $result_item['id'] = $id;
                $result_item['item_id'] = $item['id'];
                // custom DAIA field used in getHoldLink()
                $result_item['ilslink']
                    = ($item['href'] ?? $doc_href);
                // count items
                $number++;
                $result_item['number'] = $this->getItemNumber($item, $number);
                // set default value for barcode
                $result_item['barcode'] = $this->getItemBarcode($item);
                // set default value for part
                $result_item['part'] = $this->getItemPart($item);
                $result_item['about'] = $this->getItemAbout($item);

                // set default value for reserve
                $result_item['reserve'] = $this->getItemReserveStatus($item);
                // get callnumber
                $result_item['callnumber'] = $this->getItemCallnumber($item);
                // get location
                $result_item['location'] = $this->getItemLocation($item);
                // get location link
                //$result_item['locationhref'] = $this->getItemLocationLink($item);
                // status and availability will be calculated in own function
                $result_item = $this->getItemStatus($item) + $result_item;

                // add result_item to the result array
                if ($this->getItemMessage($item) !== "withdrawn") {
                    $result[] = $result_item;
                }
            } // end iteration on item
        }
        $message = $daiaArray['message'][0]['content'] ?? null;
        if (empty($result) && $message === 'Monographic component part, Text'
            && strpos($doc_id, 'koha:biblionumber:') !== false) {
            $result[] = $this->addArticleItem($doc_id);
        }

        return $result;
    }

    /**
     * Returns an array with status information for provided item.
     *
     * @param array $item Array with DAIA item data
     *
     * @return array
     */
    protected function getItemStatus($item)
    {
        $availability = false;
        $status = ''; // status cannot be null as this will crash the translator
        $duedate = null;
        $availableLink = '';
        $queue = '';
        $message = '';
        if (isset($item['message'])) {
            foreach ($item['message'] as $msg) {
                if ($msg['lang'] == 'en') {
                    $message = trim($msg['content']);
                    $pos = strpos($message, ', ');
                    if ($pos !== false) {
                        $message = substr($message, 0, $pos);
                    }
                }
            }
        }
        if (array_key_exists('available', $item)) {
            if (count($item['available']) === 1) {
                $availability = true;
            } else {
                // check if item is loanable or presentation
                foreach ($item['available'] as $available) {
                    // attribute service can be set once or not
                    if (isset($available['service'])
                        && in_array(
                            $available['service'],
                            ['loan', 'presentation', 'openaccess']
                        )
                    ) {
                        // set item available if service is loan, presentation or
                        // openaccess
                        $availability = true;
                        if ($available['service'] == 'loan'
                            && isset($available['service']['href'])
                        ) {
                            // save the link to the ils if we have a href for loan
                            // service
                            $availableLink = $available['service']['href'];
                        }
                    }

                    // use limitation element for status string
                    if (isset($available['limitation'])) {
                        $status = $this->getItemLimitation($available['limitation']);
                    }

                    // log messages for debugging
                    if (isset($available['message'])) {
                        $this->logMessages($available['message'], 'item->available');
                    }
                }
            }
        }
        if (array_key_exists('unavailable', $item)) {
            foreach ($item['unavailable'] as $unavailable) {
                // attribute service can be set once or not
                if (isset($unavailable['service'])
                    && in_array(
                        $unavailable['service'],
                        ['loan', 'presentation', 'openaccess']
                    )
                ) {
                    if ($unavailable['service'] == 'loan'
                        && isset($unavailable['service']['href'])
                    ) {
                        //save the link to the ils if we have a href for loan service
                    }

                    // use limitation element for status string
                    if (isset($unavailable['limitation'])) {
                        $status = $this
                            ->getItemLimitation($unavailable['limitation']);
                    }
                    if ($message == 'missing') {
                        $status = 'Missing';
                    } elseif ($message == 'ordered') {
                        $status = 'Ordered';
                    }

                }
                // attribute expected is mandatory for unavailable element
                if (isset($unavailable['expected'])) {
                    try {
                        $duedate = $this->dateConverter
                            ->convertToDisplayDate(
                                'Y-m-d',
                                $unavailable['expected']
                            );
                    } catch (Exception $e) {
                        $this->debug('Date conversion failed: ' . $e->getMessage());
                        $duedate = null;
                    }
                    $status = 'On loan';
                }

                // attribute queue can be set
                if (isset($unavailable['queue'])) {
                    $queue = $unavailable['queue'];
                }

                // log messages for debugging
                if (isset($unavailable['message'])) {
                    $this->logMessages($unavailable['message'], 'item->unavailable');
                }
            }
        }

        /*'availability' => '0',
        'status' => '',  // string - needs to be computed from availability info
        'duedate' => '', // if checked_out else null
        'returnDate' => '', // false if not recently returned(?)
        'requests_placed' => '', // total number of placed holds
        'is_holdable' => false, // place holding possible?*/

        if (!empty($availableLink)) {
            $return['ilslink'] = $availableLink;
        }

        $return['status'] = $status;
        $return['availability'] = $availability;
        $return['duedate'] = $duedate;
        $return['requests_placed'] = $queue;

        return $return;
    }

    /**
     * Add a new item for articles which do not have items by default
     *
     * @param $doc_id
     *
     * @return array
     */
    private function addArticleItem($doc_id)
    {
        $return = [];

        if (strpos($this->baseUrl, 'DE-Stg117') !== false) {
            $doc_id = preg_replace('/koha:biblionumber:/', '', $doc_id);
            $return = [
                'id' => $doc_id,
                'callnumber' => '',
                'location' => 'Dokumentenlieferdienst',
                'ilslink' => 'https://elk-wue.bsz-bw.de/cgi-bin/koha/opac-request-article.pl?biblionumber=' . $doc_id,
                'link' => 'https://elk-wue.bsz-bw.de/cgi-bin/koha/opac-request-article.pl?biblionumber=' . $doc_id,
                'availability' => 'article',
                'status' => 'Available',
                'checkILLRequest' => true,
                'checkStorageRetrievalRequest' => true,
                'requests_placed' => 0,
                'reserve' => false
            ];
        }
        return $return;
    }

    /**
     * Avois parsing an empty response - this may happen on ill portal if DAIA
     * is not configured correctly.
     *
     * @param type $daiaResponse
     */
    protected function convertDaiaXmlToJson($daiaResponse)
    {
        if ($daiaResponse != false && !empty($daiaResponse)) {
            return parent::convertDaiaXmlToJson($daiaResponse);
        }
        return '';
    }

    /**
     * Generate a DAIA URI necessary for the query, but remove network Prefix from
     * PPN.
     *
     * @param string $id Id of the record whose DAIA document should be queried
     *
     * @return string     URI of the DAIA document
     * @see http://gbv.github.io/daia/daia.html#query-parameters
     */
    protected function generateURI($id)
    {
        // remove the braces to get a pure ppn.
        $id = preg_replace('/\(.*\)/', '', $id);
        return parent::generateURI($id);
    }

    /**GFF
     * Returns the value for "callnumber" in VuFind getStatus/getHolding array
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemCallnumber($item)
    {
        $retval = [];
        if (isset($item['label']) && !empty($item['label'])) {
            $retval[] = $item['label'];
        }
        if (isset($item['chronology']['about']) && !empty($item['chronology']['about'])) {
            $retval[] = $item['chronology']['about'];
        }
        if (count($retval) > 0) {
            return implode(' | ', $retval);
        }
        return 'Unknown';
    }
}
