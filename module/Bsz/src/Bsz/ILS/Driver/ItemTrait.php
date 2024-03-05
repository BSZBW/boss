<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bsz\ILS\Driver;

/**
 * Contains Item methods uses by all DAIA drivers
 *
 * @author amzar
 */
trait ItemTrait
{
    /**
     *
     * @param array $item
     * @return string
     */
    public function getItemPart($item)
    {
        if (isset($item['part'])) {
            return $item['part'];
        } else {
            return '';
        }
    }

    /**
     *
     * @param array $item
     * @return string
     */
    public function getItemAbout($item)
    {
        if (isset($item['about'])) {
            return $item['about'];
        } else {
            return '';
        }
    }

    /**
     * Returns the value for "location" in VuFind getStatus/getHolding array
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemLocation($item)
    {
        $location = [];
        if (isset($item['department'])
            && array_key_exists('content', $item['department'])
        ) {
            $location[] = $item['department']['content'];
        }
        if (isset($item['storage'])
            && array_key_exists('content', $item['storage'])
        ) {
            $location[] = $item['storage']['content'];
        }
        return implode(': ', $location);
    }

    /**
     * Returns the value for "location" href in VuFind getStatus/getHolding array
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemLocationLink($item)
    {
        return $item['storage']['href'] ?? false;
    }

    /**
     * Returns the value for item "message" in VuFind getStatus/getHolding array
     *
     * @param array $item Array with DAIA item data
     *
     * @return string
     */
    protected function getItemMessage($item)
    {
        $message = "";
        if (isset($item['message'])
            && array_key_exists('content', $item['message'][0])
        ) {
            $message = $item['message'][0]['content'];
        }

        return $message;
    }
}
