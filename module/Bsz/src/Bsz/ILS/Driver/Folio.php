<?php

namespace Bsz\ILS\Driver;

class Folio extends \VuFind\ILS\Driver\Folio
{

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