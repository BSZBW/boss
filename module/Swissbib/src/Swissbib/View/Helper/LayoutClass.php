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
        $classString = parent::__invoke($class);

        $htmlLayoutClass = $this->getView()->htmlLayoutClass;

        return isset($htmlLayoutClass) ? $classString . ' ' . $htmlLayoutClass : $classString;
    }
}

 