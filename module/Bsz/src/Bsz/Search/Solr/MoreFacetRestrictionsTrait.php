<?php

namespace Bsz\Search\Solr;

use Laminas\Config\Config;

trait MoreFacetRestrictionsTrait
{
    /**
     * Per-multifield facet prefix
     *
     * @var array
     */
    protected $facetPrefixByMultiField = [];

    protected $facetPrefixByKey = [];

    protected function initMoreFacetRestrictionsFromConfig(Config $config = null)
    {
        foreach ($config->facet_prefix_by_multi_field ?? [] as $k => $v) {
            $this->facetPrefixByMultiField[$k] = $v;
        }
        foreach ($config->facet_prefix_by_key ?? [] as $k => $v) {
            $split = explode("=", $v);
            if(count($split) > 1) {
                $field = $split[0];
                $prefix = $split[1];
            }else {
                $field = $k;
                $prefix = $split[0];
            }
            $this->facetPrefixByKey[$k] = compact('field', 'prefix');
        }
    }

    /**
     * Set Facet Prefix by MultiField
     *
     * @param array $new Associative array of $multifield name => $limit
     *
     * @return void
     */
    public function setFacetPrefixByMultiField(array $new)
    {
        $this->facetPrefixByMultiField = $new;
    }

    /**
     * Get the facet prefix for the specified multifield
     *
     * used by DFI
     *
     * @param string $field MultiField to look up
     *
     * @return array
     */
    protected function  getFacetPrefixForMultiField($field)
    {
        return $this->facetPrefixByMultiField[$field] ?? [];
    }

    protected function getPrefixForKey($field)
    {
        return $this->facetPrefixByKey[$field] ?? [];
    }
}