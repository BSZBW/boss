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
     * @param String    $sort
     */
    public function setDefaultSort($defaultSort)
    {
        $this->defaultSort = $defaultSort;
    }


    /**
     * Translate a string if a translator is available.
     * We have to override this method because VF2 core doesn't support multiple Textdomains for translations at the moment
     *
     * @param string $msg Message to translate
     *
     * @return string
     */
    public function translate($msg)
    {
        return null !== $this->translator
            ? is_array($msg) && count($msg) == 2 ?  $this->translator->translate($msg[0],$msg[1]) : $this->translator->translate($msg) : $msg;
    }



}
