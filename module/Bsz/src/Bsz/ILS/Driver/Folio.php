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
     * @return string
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
                $details = $location->details->BOSS ?? '';
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
     * @return string
     */
    protected function getLocationDetailData($locationId)
    {
        $detailsMap = $this->getLocationDetails();
        $details = '';
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
                $details = $location->details->BOSS ?? '';
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

        $retVal['location_details']
            = $this->getLocationDetailData($locationId);

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

}