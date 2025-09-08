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

        $config = $this->config;
        if (!$config->FreeHold || !$config->FreeHold->url) {
            return $retVal;
        }

        foreach ($retVal as $groupKey => $items) {
            $newItems = [];
            $intellectualItem = null;
            foreach ($items['items'] as $item) {
                if (
                    ($item['is_intellectual_item'] ?? false)
                    && $intellectualItem == null
                ) {
                    $intellectualItem = $item;
                    $intellectualItem['link'] = [
                        'action' => 'FreeHold',
                        'record' => $item['link']['record'],
                        'source' => $item['link']['source'],
                        'query' => 'item_hrid='.$intellectualItem['item_hrid']
                    ];
                    $intellectualItem['linkLightbox'] = false;
                }else {
                    $newItems[] = $item;
                }
            }

            if ($intellectualItem != null) {
                $retVal[$groupKey]['intellectual_item'] = $intellectualItem;
                $retVal[$groupKey]['items'] = $newItems;
            }
        }

        return $retVal;
    }

}