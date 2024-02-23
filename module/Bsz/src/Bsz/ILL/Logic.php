<?php

/*
 * Copyright 2020 (C) Bibliotheksservice-Zentrum Baden-
 * Württemberg, Konstanz, Germany
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
namespace Bsz\ILL;

use Bsz\Exception;
use Bsz\RecordDriver\SolrMarc;
use Laminas\Config\Config;

/**
 * Class to determing availability via inter-library loan
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Logic
{
    const FORMAT_EJOURNAL = 'Ejournal';
    const FORMAT_JOURNAL = 'Journal';
    const FORMAT_EBOOK = 'Ebook';
    const FORMAT_BOOK = 'Book';
    const FORMAT_MONOSERIAL = 'MonoSerial';
    const FORMAT_ARTICLE = 'Article';
    const FORMAT_UNDEFINED = 'Undefined';

    protected $config;

    /**
     * @var SolrMarc
     */
    protected $driver;
    /**
     * @var string
     */
    protected $format;
    /**
     * @var Holding
     */
    protected $holding;
    /**
     * @var array
     */
    protected $localIsils;
    protected $swbppns = [];
    protected $parallelppns = [];
    protected $linklabels = [];
    protected $messages = [];
    protected $libraries = [];
    /**
     * @var array
     */
    protected $status;

    /**
     * @param Config $config
     * @param Holding $holding
     * @param array $isils
     */
    public function __construct(Config $config, $isils = [])
    {
        $this->config = $config;
        $this->localIsils = $isils;
        $this->localIsils[] = 'LFER';
    }

    /**
     * Each instance of this class can be used for many RecordDriver instances,
     * but not at the same time.
     *
     * @param SolrMarc $driver
     */
    public function attachDriver(SolrMarc $driver)
    {
        $this->driver = $driver;
        $this->format = $this->getFormat();
        $this->status = [];
        $this->swbppns = [];
        $this->parallelppns = [];
        $this->linklabels = [];
    }

    /**
     * Map the driver formats to more simple ILL formats
     * @return string
     * @throws Exception
     */
    private function getFormat()
    {
        $format = static::FORMAT_UNDEFINED;

        if (null === $this->driver) {
            throw new Exception('No driver set. Please attach a driver before use. ');
        }

        if ($this->driver->isElectronic()) {
            if ($this->driver->isJournal() || $this->driver->isNewspaper()) {
                $format = static::FORMAT_EJOURNAL;
            } elseif ($this->driver->isElectronicBook()) {
                $format = static::FORMAT_EBOOK;
            }
        } else {
            // Print items
            if ($this->driver->isMonographicSerial()) {
                $format = static::FORMAT_MONOSERIAL;
            } elseif ($this->driver->isPhysicalBook()) {
                $format = static::FORMAT_BOOK;
            } elseif ($this->driver->isArticle()) {
                $format = static::FORMAT_ARTICLE;
            } elseif ($this->driver->isJournal() ||
                $this->driver->isNewsPaper()
            ) {
                $format = static::FORMAT_JOURNAL;
            }
        }
        return $format;
    }

    /**
     * @param Holding $holding
     */
    public function attachHoldings(Holding $holding)
    {
        $this->holding = $holding;
    }

    /**
     * Checks if the item can be ordered via ILL
     * @return boolean
     */
    public function isAvailable()
    {
        if (empty($this->status)) {
            $this->determineStatus();
        }
        if (in_array(false, $this->status)) {
            return false;
        } else {
            $this->messages[] = 'ILL::available_for_ill';
            return true;
        }
    }

    /**
     * Fills the internal status array.
     * @return array
     * @throws Exception
     */
    protected function determineStatus()
    {
        if (null === $this->driver) {
            throw new Exception('No driver set. Please attach a driver before use. ');
        }

        $checks = $this->config->get('Checks')->get('methods');
        $checks = explode(', ', $checks);

        foreach ($checks as $check) {
            $negate = (bool)preg_match('/^!/', $check);
            $always = (bool)preg_match('/^~/', $check);

            if ($negate || $always) {
                $check = preg_replace('/^[!~]/', '', $check);
            }

            $method = 'check' . $check;

            if (method_exists($this, $method)) {
                $status = $this->$method();
                if ($negate) {
                    $status = !$status;
                } elseif ($always) {
                    $status = true;
                }
                $this->status[$check] = $status;
            }
        }
        // Avoid users to access the form if no library selected
        $this->status[] = count($this->localIsils) > 0;
        return $this->status;
    }

    /**
     * Returns the unique status code
     * TODO: this method shoudl return something from conffiguration.      *
     * @return int
     */
    public function getStatusCode()
    {
        if (empty($this->status)) {
            $this->determineStatus();
        }
        $binary = implode('', $this->status);
        return bindec($binary);
    }

    /**
     * Get all messages that occurre during processing. Messages are trans-
     * lation keys and should be translated afterwards.
     * @return array
     */
    public function getMessages()
    {
        /*
         * TODO there are still some messages set in methods. These should be
         * removed to the configuration. It might be neccessary to split those
         * methods into exactly one task.
         */
        $retval = $this->messages;
        foreach ($this->status as $check => $result) {

            // always show the hint.
            if ($check == 'JournalAvailable') {
                $result = $this->checkJournalAvailable();
                // false means record is not locally available -> don't show the hint.
                $result = !$result;
            }

            if (!$result && $this->config->get('Messages')->OffsetExists($check)) {
                $retval[] = $this->config->get('Messages')->get($check);
            }
        }
        return $retval;
    }

    /**
     * Access the PPNs found via web service
     * @return array
     */
    public function getPPNs()
    {
        $ppns = array_merge($this->parallelppns, $this->swbppns);
        return array_unique($ppns);
    }

    /**
     * Returns array of local available ISILs
     * @return array
     */
    public function getLocalIsils(): array
    {
        return $this->localIsils;
    }

    public function getLinkLabels(): array
    {
        return $this->linklabels;
    }

    /**
     * Checks whether record is from HEBIS and it's ID begins with 8
     * @return boolean
     */
    protected function checkHebis8(): bool
    {
        $network = $this->driver->getNetwork();
        $ppn = $this->driver->getPPN();

        if ($network == 'HEBIS' && preg_match('/^8/', $ppn)) {
            return true;
        }
        return false;
    }

    /**
     * Check if the record is available for free
     * @return boolean
     */
    protected function checkFree(): bool
    {
        if ($this->driver->isFree()) {
            return true;
        }
        return false;
    }

    /**
     * Determine is record is a serial or a collection
     * Journals and Newspaers are serials, too, but not included here
     * @return boolean
     */
    protected function checkSerialOrCollection(): bool
    {
        if (!$this->driver->isJournal() && !$this->driver->isNewspaper() &&
            $this->driver->isSerial() || $this->driver->isCollection()
        ) {
            return true;
        }
        return false;
    }

    /**
     * Determin if an item is available locally. Checks for
     * * 924 entries
     * * Parallel editions in SWB
     * * Similar results from SWB (if network != SWB)     *
     * @return boolean
     */
    protected function checkCurrentLibrary(): bool
    {
        $status = false;
        $network = $this->driver->getNetwork();

        // if we have local holdings, item can't be ordered - except
        // Journals and MonoSerials
        $formats = [
            static::FORMAT_JOURNAL,
            static::FORMAT_EJOURNAL,
            static::FORMAT_MONOSERIAL
        ];

        if ($this->driver->hasLocalHoldings() &&
            !in_array($this->getFormat(), $formats)
        ) {
            $this->swbppns[] = $this->driver->getPPN();
            $this->linklabels[] = 'ILL::library_opac';
            $status = true;
        } elseif ($network !== 'SWB' && $network !== 'ZDB'
            && $this->queryWebservice()
        ) {
            $status = true;
        }
        return $status;
    }

    /**
     * Query webservice to get SWB hits with the same
     * ISSN or ISBN (preferred)
     * Title, author and year (optional)
     * Found PPNs are added to ppns array and can be accessed by other methods.
     * @return boolean
     */
    protected function queryWebservice(): bool
    {
        if (!$this->holding instanceof Holding) {
            return false;
        }

        // avoid running the webservice twice
        if (count($this->swbppns) > 0) {
            return true;
        }

        // set up query params
        $this->holding->setNetwork('DE-576');
        $isbn = $this->driver->getCleanISBN();
        $years = $this->driver->getPublicationDates();
        $zdb = $this->driver->tryMethod('getZdbId');
        $year = array_shift($years);

        if ($this->driver->isArticle() || $this->driver->isJournal()
            || $this->driver->isNewspaper()
        ) {
            // prefer ZDB ID
            if (!empty($zdb)) {
                $this->holding->setZdbId($zdb);
            } else {
                $this->holding->setIsxns($this->driver->getCleanISSN());
            }
            // use ISSN and year
        } elseif (!empty($isbn)) {
            // use ISBN and year
            $this->holding->setIsxns($isbn)
                ->setYear($year);
        } else {
            // use title and author and year
            $this->holding->setTitle($this->driver->getTitle())
                ->setAuthor($this->driver->getPrimaryAuthor())
                ->setYear($year);
        }
        // check query and fire
        if ($this->holding->checkQuery()) {
            $result = $this->holding->query();
            // check if any ppn is available locally
            if (isset($result['holdings'])) {
                // search for local available PPNs
                foreach ($result['holdings'] as $ppn => $holding) {
                    foreach ($holding as $entry) {
                        if (isset($entry['isil']) && in_array($entry['isil'], $this->localIsils)) {
                            // save PPN
                            $this->swbppns[] = '(DE-627)' . $ppn;
                            $this->linklabels[] = 'ILL::to_local_hit';
                            $this->libraries[] = $entry['isil'];
                        }
                    }
                }
            }
            // if no locally available ppn found, just take the first one
            if (count($this->swbppns) < 1 && isset($result['holdings'])) {
                reset($result['holdings']);
                $this->swbppns[] = '(DE-627)' . key($result['holdings']);
                $this->linklabels[] = 'ILL::to_swb_hit';
            }
        }
        // check if any of the isils from webservic matches local isils
        if (is_array($this->libraries) && count($this->libraries) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Check if there are parallel editions available
     * @return bool
     */
    protected function checkParallelEditions(): bool
    {
        $network = $this->driver->getNetwork();
        if ($network == 'SWB' && $this->hasParallelEditions()) {
            return true;
        }
        return false;
    }

    /**
     * Quer< solr for parallel Editions available at local libraries
     * Save the found PPNs in global array
     * @return boolean
     */
    protected function hasParallelEditions()
    {
        $getIsils = function ($holdings) {
            $return = [];
            foreach ($holdings as $holding) {
                $return[] = $holding['isil'];
            }
            return $return;
        };

        if (!$this->holding instanceof Holding) {
            return false;
        }
        // avoid running the web service twice
        if (count($this->parallelppns) > 0) {
            return true;
        }
        $ppns = [];
        $related = $this->driver->tryMethod('getRelatedEditions');
        $hasParallel = false;

        foreach ($related as $rel) {
            $ppns[] = $rel['id'];
        }
        $parallel = [];
        if (count($ppns) > 0) {
            $parallel = $this->holding->getParallelEditions($ppns, $this->localIsils);
            // check the found records for local available isils
            $isils = [];
            foreach ($parallel->getResults() as $record) {
                $f924 = $record->getField924();
                $recordIsils = $getIsils($f924);
                $isils = array_merge($isils, $recordIsils);
            }
            foreach ($isils as $isil) {
                if (in_array($isil, $this->localIsils)) {
                    $hasParallel = true;
                    $this->parallelppns[] = $record->getUniqueId();
                    $this->linklabels[] = 'ILL::to_parallel_edition';
                }
            }
        }
        return $hasParallel;
    }

    /**
     * Check if it is a journal and available locally. Journals can always be
     * ordnered because we can't evauluate their exact holding dates.
     * @return bool
     */
    protected function checkJournalAvailable(): bool
    {
        if ($this->driver->hasLocalHoldings() && (
            $this->getFormat() === static::FORMAT_JOURNAL ||
                $this->getFormat() === static::FORMAT_EJOURNAL
        )
            ) {
            return true;
        }
        return false;
    }

    /**
     * Check if format is enabled for inter-library loan and if it's enabled for
     * the current network
     * @return boolean
     */
    protected function checkFormat(): bool
    {
        $section = $this->config->get($this->format);
        $network = $this->driver->getNetwork();

        $forbidden = $section->get('excludeNetwork', []);
        $forbidden = is_object($forbidden) ? $forbidden->toArray() : [];

        $enabled = $section->get('enabled');

        if ($enabled && in_array($network, $forbidden)) {
            $this->messages[] = 'ILL::cond_format_network';
            return false;
        } elseif (!$enabled) {
            $this->messages[] = 'ILL::cond_format_' . $this->format;
            return false;
        }
        return true;
    }

    /**
     * Check the ILL indicator - invalid or empty indicators are ignored
     * @return boolean
     */
    protected function checkIndicator(): bool
    {
        $f924 = $this->driver->tryMethod('getField924');
        $section = $this->config->get($this->format);
        $tmp = $section->get('indicator', []);
        $allowedCodes = is_object($tmp) ? $tmp->toArray() : $tmp;

        // for this format not indicator check applicable
        if (count($allowedCodes) == 0) {
            return true;
        }

        foreach ($f924 as $field) {
            $code = $field['ill_indicator'] ?? null;
            if (isset($code) && in_array($code, $allowedCodes)) {
                return true;
            }
        }
        return false;
    }
}
