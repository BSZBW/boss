<?php

namespace Bsz\ILS\Driver;

class Folio extends \VuFind\ILS\Driver\Folio
{

    /**
     * Gets a list of additional BOSS-specific details for
     * all available locations.
     * The implementation is based on the implementation of the
     * getLocations() helper method in class \VuFind\ILS\Driver\Folio.
     *
     * @return array
     */
    protected function getLocationDetails()
    {
        $cacheKey = 'locationDetails';
        $detailsMap = $this->getCachedData($cacheKey);
        if (null == $detailsMap) {
            $detailsMap = [];
            foreach (
                $this->getPagedResults(
                    'locations',
                    '/locations'
                ) as $location
            ) {
                $details = [
                    'description' => $location->details->BOSS ?? '',
                    'description_en' => $location->details->BOSSENG ?? '',
                    'mapongo' => $location->details->MAPONGO ?? '',
                ];
                $detailsMap[$location->id] = $details;
            }
            $this->putCachedData($cacheKey, $detailsMap);
        }
        return $detailsMap;
    }

    /**
     * Gets additional BOSS-specific details for a given location.
     * The implementation is based on the implementation of the
     * getLocationData() helper method in class \VuFind\ILS\Driver\Folio.
     *
     * @param string $locationId Location identifier from FOLIO
     *
     * @return array
     */
    protected function getLocationDetailData($locationId)
    {
        $detailsMap = $this->getLocationDetails();
        $details = [];
        if (array_key_exists($locationId, $detailsMap)) {
            return $detailsMap[$locationId];
        } else {
            // if key is not found in cache, the location could have
            // been added before the cache expired so check again
            $locationResponse = $this->makeRequest(
                'GET',
                '/locations/' . $locationId
            );
            if ($locationResponse->isSuccess()) {
                $location = json_decode($locationResponse->getBody());
                $details['description'] = $location->details->BOSS ?? '';
                $details['description_en'] = $location->details->BOSSENG ?? '';
                $details['mapongo'] = $location->details->MAPONGO ?? '';
            }
        }
        return $details;
    }

    /**
     * Extends the helper method from the base FOLIO driver by adding
     * BOSS-specific additional location details to the item.
     *
     * @param string         $locationId
     * @param array          $holdingDetails
     * @param \stdClass|null $currentLoan
     *
     * @return array
     */
    protected function getItemFieldsFromNonItemData(
        string $locationId,
        array $holdingDetails,
        ?\stdClass $currentLoan = null,
    ): array {
        $retVal = parent::getItemFieldsFromNonItemData(
            $locationId,
            $holdingDetails,
            $currentLoan
        );

        $retVal['location_details'] = $this->getLocationDetailData($locationId);
        return $retVal;
    }

    public function getHolding(
        $bibId,
        array $patron = null,
        array $options = []
    ) {
        $retVal = parent::getHolding(
            $bibId,
            $patron,
            $options
        );

        $newItems = [];
        foreach ($retVal['holdings'] as $item) {
            $item['is_intellectual_item'] =
                'Intellectual item' == ($item['status'] ?? '');
            $newItems[] = $item;
        }
        $retVal['holdings'] = $newItems;
        return $retVal;
    }

    protected function formatHoldingItem(
        string $bibId,
        array $holdingDetails,
        $item,
        $number,
        string $dueDateValue,
        $boundWithRecords,
        $currentLoan
    ): array {
        $retVal =  parent::formatHoldingItem(
            $bibId,
            $holdingDetails,
            $item,
            $number,
            $dueDateValue,
            $boundWithRecords,
            $currentLoan
        );
        $retVal['item_hrid'] = $item->hrid ?? '';
        return $retVal;
    }


    protected function getInstanceByBibId($bibId)
    {
        $bibId = preg_replace('/\(.*\)/', '', $bibId);
        return parent::getInstanceByBibId($bibId);
    }

    public function getStatuses($idList)
    {
        $ids = [];
        foreach ($idList as $id) {
            $ids[] = preg_replace('/\(.*\)/', '', $id);
        }
        return parent::getStatuses($ids);
    }

    public function patronLogin($username, $password)
    {
        $retVal =  parent::patronLogin($username, $password);
        $profile = $this->getUserById($retVal['id']);
        return $retVal + [
            'externalSystemId' => $profile->externalSystemId ?? null
            ];
    }

    public function getMyHolds($patron)
    {
        $holds = parent::getMyHolds($patron);
        foreach ($holds as &$hold) {
            $response = $this->makeRequest(
            'GET',
            '/circulation/requests/' . $hold['reqnum']
            );
            if ($response->isSuccess()) {
                $request = json_decode($response->getBody());
                if($request->pickupServicePoint) {
                    $hold = $hold + [
                        'pickupServicePoint' => $request->pickupServicePoint
                    ];
                }
            }
        }
        return $holds;
    }

    /**
     * This method queries the ILS for a patron's current ILLRequests
     * (OKAPI-)Systemuser needs the following permission:
     * - rs.patronrequests.collection.get
     *
     * Therefor this permission had to be added in Folio for
     * (OKAPI-)Systemuser:
     * - ILL Request: View requests
     *
     * And for future safety, also these permissions:
     * - patronrequests collection get
     * - patronrequests item get
     * - patronrequests read
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return array
     */
    public function getMyILLRequests($patron)
    {
        // Identify patron
        $patronrequests = [];
        $patronIdentifier = $patron["username"]; // the user-barcode
        if ($patronIdentifier === '') {
            throw new \VuFind\Exception\ILS('The patron identifier is empty');
        }

        $pageSize = 100;

        // Build query to filter the requests the library got
        $query = [
            'filters' => 'patronIdentifier==' . $patronIdentifier
                . '&&isRequester==true',
            'stats' => 'true',
            'perPage' => $pageSize,
            'sort' => 'id',
        ];

        // Send query to endpoint
        foreach (
            $this->getPagedResults(
                'results',
                '/rs/patronrequests',
                $query,
                $pageSize
            ) as $patronrequest
        ) {
            // Precaution: do not send the requests of other patrons
            if (($patronrequest->patronIdentifier ?? null) !== $patronIdentifier) {
                throw new \VuFind\Exception\ILS(
                    'Reshare sent requests of another patron.'
                );
            }

            // Build array for illrequest.phtml
            $currentRequest = [
                'author' => $patronrequest->author ?? '',
                'id' => $patronrequest->hrid ?? '',
                'title' => $patronrequest->title ?? '',
                'subtitle' => $patronrequest->subtitle ?? '',
                'publisher' => $patronrequest->publisher ?? '',
                'placeOfPublication' => $patronrequest->placeOfPublication ?? '',
                'volumes' => $patronrequest->volumes[0] ?? '',
                'create' => $this->dateConverter->convertToDisplayDate(
                        'Y-m-d',
                        $patronrequest->dateCreated ?? ''
                    ),
                'expire' => $this->dateConverter->convertToDisplayDate(
                        'Y-m-d',
                        $patronrequest->neededBy ?? ''
                    ),
                'location' => $patronrequest->pickupLocation ?? '',
                'patronNote' => $patronrequest->patronNote ?? ''
            ];
            $patronrequests[] = $currentRequest;
        }
        return $patronrequests;
    }

    public function getMyProfile($patron)
    {
        $retVal = parent::getMyProfile($patron);
        $profile = $this->getUserById($patron['id']);
        $fullName = trim(($retVal['firstname'] ?? '') . ' ' . ($retVal['lastname'] ?? ''));
        $zipAndCity = trim(($retVal['zip'] ?? '') . ' ' . ($retVal['city'] ?? ''));
        return $retVal + [
            'fullname' => $fullName ?? null,
            'zip_and_city' => $zipAndCity ?? null,
            'barcode' => $profile->barcode ?? null,
            'email' => $profile->personal->email ?? null,
        ];
    }

    public function getMyTransactions($patron, $params = [])
    {
        $retVal =  parent::getMyTransactions($patron, $params);
        foreach ($retVal['records'] as &$trans) {
            $response = $this->makeRequest(
                'GET',
                '/inventory/items/' . $trans['item_id']
            );
            if ($response->isSuccess()) {
                $item = json_decode($response->getBody());
                $trans += [
                    'callnum' => $item->callNumber ?? null
                ];
            }

            $currentLoan = $this->getCurrentLoan($trans['item_id']);
            if ($currentLoan == null || $currentLoan->userId != $patron['id']) {
                continue;
            }
            $query = [
                'limit' => '1',
                'query' => '(items="'.$currentLoan->id.'" and action=="Patron info added") sortBy date/sort.descending'
            ];
            $logResponse = $this->makeRequest(
                'GET',
                '/audit-data/circulation/logs',
                $query
            );
            if($logResponse->isSuccess()) {
                $log = json_decode($logResponse->getBody());
                if(!empty($log->logRecords)) {
                    $trans += [
                        'patronInfo' => $log->logRecords[0]->description ?? null
                    ];
                }
            }
        }
        return $retVal;
    }


}