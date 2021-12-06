<?php
/**
 * ILS Driver for VuFind to query availability information via DAIA.
 *
 * Based on the proof-of-concept-driver by Till Kinstler, GBV.
 * Relaunch of the daia driver developed by Oliver Goldschmidt.
 *
 * PHP version 5
 *
 * Copyright (C) Jochen Lienhard 2014.
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
 * @package  ILS_Drivers
 * @author   Jochen Lienhard <lienhard@ub.uni-freiburg.de>
 * @author   Oliver Goldschmidt <o.goldschmidt@tu-harburg.de>
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:building_an_ils_driver Wiki
 */
namespace Bsz\ILS\Driver;

use VuFind\Exception\ILS as ILSException;

/**
 * ILS Driver for VuFind to query availability information via DAIA.
 *
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
     *
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
     *
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     *
     * @throws ILSException
     * @return void
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
     *
     * The goal for this method is to return a URL to a "place hold" web page on
     * the ILS OPAC. This is used for ILSs that do not support an API or method
     * to place Holds.
     *
     * Uses the mobile version of aDIS by exchanging a number
     *
     * @param string $id      The id of the bib record
     * @param array  $details Item details from getHoldings return array
     *
     * @return string         URL to ILS's OPAC's place hold screen.
     *
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

    /**
     * Perform an HTTP request.
     *
     * @param string $id id for query in daia
     *
     * @return xml or json object
     * @throws ILSException
     */
    protected function doHTTPRequest($id)
    {
        $contentTypes = [
            "xml"  => "application/xml",
            "json" => "application/json",
        ];

        $http_headers = [
            "Content-type: " . $contentTypes[$this->daiaResponseFormat],
            "Accept: " . $contentTypes[$this->daiaResponseFormat]
        ];

        if ($this->parsePpn || strpos($id, ')') !== false) {
            $end = strpos($id, ')');
            $ppn = substr($id, $end + 1);

            $params = [
                "id" => $this->daiaIdPrefix . $ppn,
                "format" => $this->daiaResponseFormat,
            ];
        } else {
            $params = [
                "id" => $this->daiaIdPrefix . $id,
                "format" => $this->daiaResponseFormat,
            ];
        }

        try {
            $result = $this->httpService->get(
                $this->baseUrl,
                $params, null, $http_headers
            );
        } catch (\Exception $e) {
            throw new \VuFind\Exception\ILS($e->getMessage());
        }

        if (!$result->isSuccess()) {
            // throw ILSException disabled as this will be shown in VuFind-Frontend
            //throw new ILSException('HTTP error ' . $result->getStatusCode() .
            //                       ' retrieving status for record: ' . $id);
            // write to Debug instead
            $this->debug(
                'HTTP status ' . $result->getStatusCode() .
                ' received, retrieving availability information for record: ' . $id
            );

            // return false as DAIA request failed
            return false;
        }
        return $result->getBody();
    }

    /**
     * Get Status
     *
     * This is responsible for retrieving the status information of a certain
     * record.
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber.
     */
    public function getStatus($id)
    {
        if (!array_key_exists($id, $this->holdings)) {
            try {
                $rawResult = $this->doHTTPRequest($this->generateURI($id));
                // extract the DAIA document for the current id from the
                // HTTPRequest's result
                $doc = $this->extractDaiaDoc($id, $rawResult);
                if ($doc !== null) {
                    // parse the extracted DAIA document and return the status info
                    $this->holdings[$id] = $this->parseDaiaDoc($id, $doc);
                    return $this->holdings[$id];
                } else {
                    $this->holdings[$id] = [];
                }
            } catch (ILSException $e) {
                $this->debug($e->getMessage());
            }
        } else {
            return $this->holdings[$id];
        }
    }

    /**
     * Parse an array with DAIA status information.
     *
     * @param string $id        Record id for the DAIA array.
     * @param array  $daiaArray Array with raw DAIA status information.
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
                    }
                }
                // attribute expected is mandatory for unavailable element
                if (isset($unavailable['expected'])) {
                    try {
                        $duedate = $this->dateConverter
                            ->convertToDisplayDate(
                                'Y-m-d', $unavailable['expected']
                            );
                    } catch (\Exception $e) {
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

        $return['status']          = $status;
        $return['availability']    = $availability;
        $return['duedate']         = $duedate;
        $return['requests_placed'] = $queue;

        return $return;
    }

    /**
     * Needed to hide holdings tab if empty
     * @param string $id
     * @return boolean
     */
    public function hasHoldings($id)
    {
        $holdings = $this->getHolding($id);

        if (isset($holdings) && count($holdings) > 0) {
            // Filter out unwanted statuses
            foreach ($holdings as $holding) {
                if ($holding['callnumber'] == 'Unknown') {
                    return false;
                }
            }
            return true;
        }
        // No holdings found
        return false;
    }

    public function translationEnabled()
    {
        if (isset($this->config['DAIA']['noTranslation'])) {
            return false;
        }
        return true;
    }

    public function getNewItems()
    {
        return [];
    }

    public function getDepartments()
    {
        return [];
    }

    public function getInstructors()
    {
        return [];
    }

    public function getCourses()
    {
        return [];
    }

    /**
     * Avois parsing an empty response - this may happen on ill portal if DAIA
     * is not configured correctly.
     * @param type $daiaResponse
     */
    protected function convertDaiaXmlToJson($daiaResponse)
    {
        if ($daiaResponse != false && !empty($daiaResponse)) {
            return parent::convertDaiaXmlToJson($daiaResponse);
        }
        return '';
    }
}
