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
                    if (isset($parent[$k]['link'])) {
                        $parent[$k]['ilslink'] = $parent[$k]['link'];
                    }
                    unset($parent[$k]['link']);
                }
            } else {
                unset($parent[$k]);
            }
        }

        return array_values($parent);
    }


}
