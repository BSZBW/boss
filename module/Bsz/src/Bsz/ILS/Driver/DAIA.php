<?php

/*
 * The MIT License
 *
 * Copyright 2016 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
namespace Bsz\ILS\Driver;

use DateTime;
use Exception;
use VuFind\Date\Converter;
use VuFind\Exception\ILS as ILSException;

/**
 * Description of DAIAaDis
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class DAIA extends \VuFind\ILS\Driver\DAIA
{
    use ItemTrait;

    protected $isil;
    protected $parsePpn = true;
    protected $holdings = [];

    public function __construct(Converter $converter, $isil, $baseUrl = '')
    {
        $this->dateConverter = $converter;
        $this->isil = $isil;
        if (strlen($baseUrl) > 0) {
            $this->baseUrl = $baseUrl;
        }
    }

/* Perform an HTTP request.
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

        $ppn = $id;
        // cut the braces away
        if (strpos($id, ')') !== false) {
            $end = strpos($id, ')');
            $ppn = substr($id, $end + 1);
        }

        $params = [
            "id" => $ppn,
        ];

        try {
            $result = $this->httpService->get(
                $this->baseUrl,
                $params,
                null,
                $http_headers
            );
        } catch (Exception $e) {
            throw new ILSException($e->getMessage());
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

    /**
     * This method adds status, availability, duedate, requests_placed
     * to response array
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
        $message = [];

        if (isset($item['message']) && is_array($item['message'])) {
            foreach ($item['message'] as $msg) {
                if (isset($msg['lang'])) {
                    $message[$msg['lang']] = trim($msg['content']);
                }
            }
        }
        if (array_key_exists('available', $item)) {
            // check if item is loanable or presentation
            $available = $this->getAvailableServices($item);

            if (array_key_exists('loan', $available)
                    && array_key_exists('presentation', $available)) {
                $status = 'Loan';
                $availability = true;
            } elseif (array_key_exists('loan', $available)
                    && !array_key_exists('openaccess', $available)) {
                $status = 'In store';
                $availability = true;
            } elseif (array_key_exists('presentation', $available)) {
                $status = 'For reference';
                $availability = true;
            }

            // log messages for debugging
            if (isset($available['message'])) {
                $this->logMessages($available['message'], 'item->available');
            }
        } elseif (array_key_exists('unavailable', $item)) {
            foreach ($this->getUnvailableServices($item) as $unavailable) {
                // attribute service can be set once or not
                if (isset($unavailable['service'])
                    && array_key_exists(
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
                // items unavailable with duedate set
                if (isset($unavailable['expected'])) {
                    $duedateRaw = $unavailable['expected'];

                    try {
                        $dateObject = new DateTime($duedateRaw);
                        $dateToday = new DateTime();
                        $difference = $dateToday->diff($dateObject)->days;
                        $duedate = $dateObject->format('d.m.Y');
                    } catch (Exception $ex) {
                        $this->debug('Date conversion failed: ' . $ex->getMessage());
                        $duedate = null;
                    }

                    if (isset($difference) && $difference > 365) {
                        $status = 'Permanent on loan';
                    } else {
                        $status = 'On Loan';
                    }
                } else {
                    // no items available
                    $status = 'Unavailable';
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
        $return['message']         = $message;
        $return['status']          = $status;
        $return['availability']    = $availability;
        $return['duedate']         = $duedate;
        $return['requests_placed'] = $queue;

        return $return;
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
                // E books do not have valid items set
                if (isset($item['id']) && $item['id'] == 'E-Book') {
                    continue;
                }
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
//                // get location link
//                $result_item['locationhref'] = $this->getItemLocationLink($item);
                // status and availability will be calculated in own function
                $result_item = $this->getItemStatus($item) + $result_item;
                // add result_item to the result array
                $result[] = $result_item;
            } // end iteration on item
        }
        return $result;
    }

    /**
     * Get a list of all available services
     *
     * @param array $item
     *
     * @return array
     */
    protected function getAvailableServices($item)
    {
        $available = [];
        foreach ($item['available'] as $service) {
            $available[$service['service']] = $service;
        }
        return $available;
    }

    /**
     * Get a list of all unavailable services
     *
     * @param array $item
     *
     * @return array
     */
    protected function getUnvailableServices($item)
    {
        $unavailable = [];
        foreach ($item['unavailable'] as $service) {
            if ($service['service'] !== 'interloan' && $service['service'] !== 'openaccess') {
                $unavailable[$service['service']] = $service;
            }
        }
        return $unavailable;
    }

    /**
     * Get Hold Link
     *
     * The goal for this method is to return a URL to a "place hold" web page on
     * the ILS OPAC. This is used for ILSs that do not support an API or method
     * to place Holds.
     *
     * Switch to mobile version of OPAC
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
        if (isset($details['ilslink']) && $details['ilslink'] != '') {
            return $details['ilslink'];
        }
        return '';
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
        } else {
            throw new ILSException('DAIA/baseUrl configuration needs to be set.');
        }
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
     * Needed to hide holdings tab if empty
     * @param string $id
     * @return boolean
     */
    public function hasHoldings($id)
    {
        // we can't query DAIA without an ISIL.
        if (empty($this->isil)) {
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
}
