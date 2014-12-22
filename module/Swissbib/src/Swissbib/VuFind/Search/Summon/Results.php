<?php


namespace Swissbib\Vufind\Search\Summon;

use SerialsSolutions_Summon_Query as SummonQuery,
    VuFind\Exception\RecordMissing as RecordMissingException,
    VuFind\Search\Base\Results as BaseResults,
    VuFind\Solr\Utils as SolrUtils,
    VuFindSearch\ParamBag;

use VuFind\Search\Summon\Results as VFSummonResults;


class Results extends VFSummonResults
{

    /**
     * @var String
     */
    protected $target = 'summon';



    /**
     * Turn the list of spelling suggestions into an array of urls
     *   for on-screen use to implement the suggestions.
     *
     * @return array Spelling suggestion data arrays
     */
    public function getSpellingSuggestions()
    {
        $retVal = array();
        foreach ($this->getRawSuggestions() as $term => $details) {
            foreach ($details['suggestions'] as $word) {
                // Strip escaped characters in the search term (for example, "\:")
                $term = stripcslashes($term);
                $word = stripcslashes($word);
                // strip enclosing parentheses
                $from = array( '/^\(/', '/\)$/');
                $to = array('','');
                $term = preg_replace($from, $to, $term);
                $word = preg_replace($from, $to, $word);
                $retVal[$term]['suggestions'][$word] = array('new_term' => $word);
            }
        }
        return $retVal;
    }



    /**
     * @return String $target
     */
    public function getTarget()
    {
      return $this->target;
    }

    public function getMyLibrariesFacets()
    {
        return array();
    }


    /**
     *
     * Returns the stored list of facets for the last search
     * GH:
     * The function is overridden because the current VF2 implementation does'nt take care (22.12.2014) about the number of facets shown for each category.
     * It always displays a fix number (at the moment five items defined in the Recommend\SideFacets.phtml)
     * Because we implemented a more flexible definition for Solr it has to be changed somehow for Summon too. I guess this is matter of change in the future by VuFind
     * so I don't invest too much into it
     *
     * @param array $filter Array of field => on-screen description listing
     * all of the desired facet fields; set to null to get all configured values.
     *
     * @return array        Facets data arrays
     */
    public function getFacetList($filter = null)
    {

        $finalResult = parent::getFacetList($filter);
        $configResultSettings = $this->getServiceLocator()->get('VuFind\Config')->get('Summon')->Results_Settings;
        if ($configResultSettings) {
            $defaultLimit  = $configResultSettings->facet_limit_default ? $configResultSettings->facet_limit_default : 4;
            foreach ($finalResult as $key => $value ) {

                $finalResult[$key]['displayLimit'] = isset($configResultSettings->{'facet_limit_' . $key}) ? $configResultSettings->{'facet_limit_' . $key} :
                                                $defaultLimit;
            }
        } else {
            // in case something isn't configured as expected,
            // we need to define a displayLimit for the category otherwise the template using it stumbled upon it
            foreach ($finalResult as $key => $value ) {
                $finalResult[$key]['displayLimit'] = 5;
            }
        }
        return $finalResult;
    }


}
