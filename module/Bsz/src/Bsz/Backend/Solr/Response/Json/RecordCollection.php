<?php

/**
 * Simple JSON-based record collection.
 *
 * @category Ida
 * @package  Search
 * @author   <dku@outermedia.de>
 */
namespace Bsz\Backend\Solr\Response\Json;

class RecordCollection extends \VuFindSearch\Backend\Solr\Response\Json\RecordCollection
{
    /**
     * Grouping field name if exists.
     *
     * @var string
     */
    protected $groupFieldName;

    /**
     * Constructor.
     *
     * @param array $response Deserialized SOLR response
     *
     * @return void
     */
    public function __construct(array $response)
    {
        if (
            array_key_exists('response', $response)
            && null === $response['response']
        ) {
            unset($response['response']);
        }
        $this->response = array_replace_recursive(static::$template, $response);

        if (true === $this->isGrouped()) {

            // Extract grouping field name
            $keys = $this->getGroups();
            $reset = array_keys($keys);
            $this->groupFieldName = reset($reset);

            $this->offset = 0; // TODO: No "start" info provided
        } else {
            $this->offset = $this->response['response']['start'];
        }

        $this->rewind();
    }

    public function isGrouped()
    {
        $groups = $this->getGroups();

        return 0 < count($groups);
    }

    /**
     * Return total number of records found.
     *
     * @return int
     */
    public function getTotal()
    {
        return true === $this->isGrouped()
            ? $this->response['stats']['stats_fields'][$this->groupFieldName]['cardinality']
            : $this->response['response']['numFound'];
    }
}
