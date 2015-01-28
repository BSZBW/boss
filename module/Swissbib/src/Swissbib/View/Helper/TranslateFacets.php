<?php
namespace Swissbib\View\Helper;

use Zend\I18n\Translator\Translator;
use Zend\I18n\View\Helper\AbstractTranslatorHelper;
use Swissbib\VuFind\View\Helper\Root\Translate as SwissbibTranslate;

/**
 * Translate locations
 *
 */
class TranslateFacets extends SwissbibTranslate
{

    /*
     * array of facets to be translated (with optional translation domain
     * facet name:domain name
     * @var array
     */
    private $translatedFacets = array();


    public function __construct($translatedFacets = array())
    {

        $this->translatedFacets = $translatedFacets;

    }



    public function __invoke($facetName,$facetValue)
    {
        $fieldToTranslateInArray =  array_filter($this->translatedFacets,function ($passedValue) use ($facetName){
            return $passedValue === $facetName || count(preg_grep ( "/" .$facetName . ":" . "/", array ($passedValue))) > 0;
        }) ;

        $translate = count($fieldToTranslateInArray) > 0;
        $fieldToEvaluate = $translate ? current($fieldToTranslateInArray) : null;

        return $translate ? strstr($fieldToEvaluate,':') === FALSE ? $this->processTranslation($facetValue) :
            //$this->processTranslation(array($facetValue , substr($fieldToEvaluate,strpos( $fieldToEvaluate,':') + 1 )))  : $facetValue;
            $this->processTranslation(substr($fieldToEvaluate,strpos( $fieldToEvaluate,':') + 1) . '::' .   $facetValue) : $facetValue;


    }


}
