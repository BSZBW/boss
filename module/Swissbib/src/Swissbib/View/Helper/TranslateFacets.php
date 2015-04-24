<?php
namespace Swissbib\View\Helper;

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


    /**
     * @param array $translatedFacets
     */
    public function __construct($translatedFacets = array())
    {
        $this->translatedFacets = $translatedFacets;
    }


    /*
     * @param array  $str     Must be an array because we need multiple values ['facetName' => 'name', 'facetValue' => 'value']
     * @param array  $tokens  Tokens to inject into the translated string
     * @param string $default Default value to use if no translation is found (null
     * for no default).
     *
     * @return string
     */
    public function __invoke($str, $tokens = array(), $default = null)
    {
        if (!is_array($str)) return '';

        $facetName = $str['facetName'];
        $facetValue = $str['facetValue'];

        $fieldToTranslateInArray =  array_filter($this->translatedFacets,function ($passedValue) use ($facetName){
            return $passedValue === $facetName || count(preg_grep ( "/" .$facetName . ":" . "/", array ($passedValue))) > 0;
        }) ;

        $translate = count($fieldToTranslateInArray) > 0;
        $fieldToEvaluate = $translate ? current($fieldToTranslateInArray) : null;

        return $translate ? strstr($fieldToEvaluate,':') === FALSE ? $this->processTranslation($facetValue) :
            $this->processTranslation(substr($fieldToEvaluate,strpos( $fieldToEvaluate,':') + 1) . '::' .   $facetValue) : $facetValue;
    }


}
