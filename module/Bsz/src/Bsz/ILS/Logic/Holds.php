<?php

namespace Bsz\ILS\Logic;

class Holds extends \VuFind\ILS\Logic\Holds
{
    /**
     * Extends the base method by adding BOSS-specific location details
     * from the items.
     *
     * @param array $holdings An associative array of location => item array
     *
     * @return array
     */
    protected function formatHoldings($holdings)
    {
        $retVal = parent::formatHoldings($holdings);
        foreach ($holdings as $groupKey => $items) {
            $retVal[$groupKey]['location_details']
                = $items[0]['location_details'] ?? '';
        }
        return $retVal;
    }

}