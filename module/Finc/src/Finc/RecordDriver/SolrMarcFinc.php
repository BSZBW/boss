<?php
/**
 * finc specific model for MARC records with a fullrecord in Solr.
 *
 * PHP version 5
 *
 * Copyright (C) Leipzig University Library 2015.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
namespace Finc\RecordDriver;

use Bsz\RecordDriver\MarcFormatTrait;
use Laminas\Config\Config;

/**
 * finc specific model for MARC records with a fullrecord in Solr.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   André Lahmann <lahmann@ub.uni-leipzig.de>
 * @author   Gregor Gawol <gawol@ub.uni-leipzig.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:record_drivers Wiki
 */
class SolrMarcFinc extends SolrMarc
{
    use SolrMarcFincTrait;
    use MarcFormatTrait;

    /**
     * pattern to identify bsz
     */
    const BSZ_PATTERN = '/^(\(DE-576\))(\d+)(\w|)/';

    /**
     * List of isil of institution
     *
     * @var string  ISIL of this instance's library
     */
    protected $isil = [];

    /**
     * Local marc field of institution participated in Finc.
     *
     * @var  string|null
     * @link https://intern.finc.info/fincproject/projects/finc-intern/wiki/FincMARC_-_Erweiterung_von_MARC21_f%C3%BCr_finc
     */
    protected $localMarcFieldOfLibrary = null;

    /**
     * Constructor
     *
     * @param Config $mainConfig     VuFind main configuration (omit for
     * built-in defaults)
     * @param Config $recordConfig   Record-specific configuration file
     * (omit to use $mainConfig as $recordConfig)
     * @param Config $searchSettings Search-specific configuration file
     */
    public function __construct(
        Config $mainConfig = null,
        Config $recordConfig = null,
        Config $searchSettings = null
    ) {
        parent::__construct($mainConfig, $recordConfig, $searchSettings);

        // get the isil set in InstitutionInfo in config.ini
        if (isset($mainConfig->InstitutionInfo->isil)
            && $mainConfig->InstitutionInfo->isil
        ) {
            $this->isil = $this->mainConfig->InstitutionInfo->isil->toArray();
        } else {
            $this->debug('InstitutionInfo setting: isil is missing.');
        }
    }

    /**
     * Get an array of all the formats associated with the record. The array is
     *  already simplified and unified.
     *
     * @return array
     */

    public function getFormats() : array
    {
        if ($this->formats === null && isset($this->formatConfig)) {
            $formats = [];
            if ($this->isElectronic()) {
                $formats[] = 'Online';
            }
            $formats[] = $this->getFormatMarc();
            $formats[] = $this->getFormatRda();

            $this->formats = $this->simplifyFormats($formats);
        }
        return $this->formats;
    }
}
