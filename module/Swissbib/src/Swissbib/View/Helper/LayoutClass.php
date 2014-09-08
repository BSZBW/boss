<?php
/**
 * Created by PhpStorm.
 * User: nkarrer
 * Date: 30.07.14
 * Time: 13:09
 */

namespace Swissbib\View\Helper;
use Swissbib\Libadmin\Exception\Exception;


/**
 * Class LayoutClass
 * @package	Swissbib\View\Helper
 * @author	Nicolas Karrer <nkarrer@snowflake.ch>
 */
class LayoutClass extends \VuFind\View\Helper\Bootstrap3\LayoutClass {

    /**
     * @param   string  $class
     * @return  string
     */
    public function __invoke($class) {
        $classString = '';

        switch ($class) {
        case 'mainbody':
            $classString.= $this->left ? 'col-md-9 col-md-push-3 col-table-fix-md' : 'col-md-9 col-table-fix-md';
        break;
        case 'sidebar':
            $classString.= $this->left
                ? 'sidebar col-md-3 col-md-pull-9 col-table-fix-md hidden-print'
                : 'sidebar col-md-3 col-table-fix-md hidden-print';
        }

        $htmlLayoutClass = $this->getView()->htmlLayoutClass;

        return isset($htmlLayoutClass) ? $classString . ' ' . $htmlLayoutClass : $classString;
    }
}

 