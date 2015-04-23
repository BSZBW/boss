<?php
namespace Swissbib\VuFind\Search\Solr;

use VuFind\Search\Solr\Options as VuFindSolrOptions;

/*
 * Class to extend the core VF2 SOLR functionality related to Options
 */
class Options extends VuFindSolrOptions
{

    /**
     * Set default limit
     *
     * @param    Integer        $limit
     */
    public function setDefaultLimit($limit)
    {
        $this->defaultLimit = intval($limit);
    }


    /**
     * @param String    $defaultSort
     */
    public function setDefaultSort($defaultSort)
    {
        $this->defaultSort = $defaultSort;
    }


    /**
     * Translate a string if a translator is available.
     * We have to override this method because VF2 core doesn't support multiple Textdomains for translations at the moment
     * @override
     *
     * @param string $str     String to translate
     * @param array  $tokens  Tokens to inject into the translated string
     * @param string $default Default value to use if no translation is found (null
     * for no default).
     *
     * @return string
     */
    public function translate($str, $tokens = [], $default = null)
    {
        return null !== $this->translator
            ? is_array($str) && count($str) == 2 ?  $this->translator->translate($str[0],$str[1]) : $this->translator->translate($str) : $str;
    }



}
