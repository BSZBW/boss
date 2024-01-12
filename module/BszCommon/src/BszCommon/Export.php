<?php

namespace BszCommon;

class Export extends \VuFind\Export
{
    public function getFormatsForRecord($driver)
    {
        // Get an array of enabled export formats (from config, or use defaults
        // if nothing in config array).
        $active = $this->getActiveFormats('record');
        $exportFormats = array_keys($this->exportConfig->toArray());

        $formats = [];
        foreach ($active as $format) {
            if($this->recordSupportsFormat($driver, $format) && in_array($format, $exportFormats)) {
                $formats[] = $format;
            }
        }
        return $formats;
    }

}