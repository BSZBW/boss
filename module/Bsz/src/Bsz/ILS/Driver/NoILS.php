<?php
namespace Bsz\ILS\Driver;

use Bsz\Config\Libraries;
use VuFind\Exception\ILS as ILSException;

/**
 * Description of NoILS
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class NoILS extends \VuFind\ILS\Driver\NoILS
{
    /**
     *
     * @var Libraries
     */
    protected $libraries;

    protected $isils;

    public function __construct(\VuFind\Record\Loader $loader, Libraries $libraries, $isils)
    {
        $this->libraries = $libraries;
        $this->isils = $isils;
        parent::__construct($loader);
    }

    /**
     * Get Holding
     *
     * This is responsible for retrieving the holding information of a certain
     * record.
     *
     * @param string $id     The record id to retrieve the holdings for
     * @param array  $patron Patron data
     *
     * @throws ILSException
     * @return array         On success, an associative array with the following
     * keys: id, availability (boolean), status, location, reserve, callnumber,
     * duedate, number, barcode.
     */
    public function getHolding($id, array $patron = null, array $options = [])
    {
        $useHoldings = isset($this->config['settings']['useHoldings'])
            ? $this->config['settings']['useHoldings'] : 'none';

        if ($useHoldings == "custom") {
            return [
                [
                    'id' => $id,
                    'number' => $this->translate(
                        $this->config['Holdings']['number']
                    ),
                    'availability' => $this->config['Holdings']['availability'],
                    'status' => $this->translate(
                        $this->config['Holdings']['status']
                    ),
                    'use_unknown_message' =>
                        $this->config['Holdings']['use_unknown_message'],
                    'location' => '',
                    'reserve' => $this->config['Holdings']['reserve'],
                    'callnumber' => $this->translate(
                        $this->config['Holdings']['callnumber']
                    ),
                    'barcode' => $this->config['Holdings']['barcode'],
                    'notes' => isset($this->config['Holdings']['notes'])
                        ? $this->config['Holdings']['notes'] : [],
                    'summary' => isset($this->config['Holdings']['summary'])
                        ? $this->config['Holdings']['summary'] : []
                ]
            ];
        } elseif ($useHoldings == "marc") {
            // Retrieve record from index:
            xdebug_break();
            $recordDriver = $this->getSolrRecord($id);
            return $this->getFormattedMarcDetails($recordDriver, 'MarcHoldings');
        }

        return [];
    }

    /**
     * This is responsible for retrieving the status or holdings information of a
     * certain record from a Marc Record.
     *
     * @param object $recordDriver  A RecordDriver Object
     * @param string $configSection Section of driver config containing data
     * on how to extract details from MARC.
     *
     * @return array An Array of Holdings Information
     */
    protected function getFormattedMarcDetails($recordDriver, $configSection)
    {
        $parent = parent::getFormattedMarcDetails($recordDriver, $configSection);
        foreach ($parent as $k => $item) {
            $currentIsil = $item['location'];
            if (in_array($currentIsil, $this->isils)) {
                $library = $this->libraries->getByIsil($currentIsil);
                if (isset($library)) {
                    $parent[$k]['location'] = $library->getName();
                    $parent[$k]['locationhref'] = $library->getHomepage();
                }
            } else {
                unset($parent[$k]);
            }
        }

        return $parent;
    }

    /**
     * Has Holdings
     *
     * This is responsible for determining if holdings exist for a particular
     * bibliographic id
     *
     * @param string $id The record id to retrieve the holdings for
     *
     * @return bool True if holdings exist, False if they do not
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function hasHoldings($id)
    {
        $parent = parent::hasHoldings($id);
        $marc = $this->getHolding($id);
        if (count($marc) > 0 && $parent) {
            return true;
        }
        return false;
    }
}
