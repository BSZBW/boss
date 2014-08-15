<?php
namespace Swissbib;
use VuFind\Export as VFExport;

/**
 * Class Export
 * @package	Swissbib
 * @author	Nicolas Karrer <nkarrer@snowflake.ch>
 */
class Export extends VFExport {

    /**
     * @param   String  $format
     * @return  String
     */
    public function getVisibilityClassName($format) {
        $visibilityClassName = $this->exportConfig->$format->visibilityClassName;

        return isset($visibilityClassName) ?
            $visibilityClassName : '';
    }
}

 