<?php

/**
 * Record driver view helper
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */

namespace BszTheme\View\Helper\Bodensee;

use Zend\View\Exception\RuntimeException,
    Zend\View\Helper\AbstractHelper;

/**
 * Record driver view helper
 *
 * @category VuFind2
 * @package  View_Helpers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/vufind2:developer_manual Wiki
 */
class Record extends \VuFind\View\Helper\Root\Record
{

    /**
     * Client Model
     * @var type \Bsz\Config\Client
     */
    protected $client;

    /**
     *
     * @var \Bsz/Holding
     */
    protected $holding;

    /**
     *
     * @var array
     */
    protected $libraries = false;
    
    /**
     *
     * @var array;
     */
    protected $ppns = [];
    
    protected $localIsils = [];
    
    /**
     *
     * @var bool 
     */
    protected $atCurrentLibrary = false;

    /**
     * Constructor
     *
     * @param \Zend\Config\Config $config VuFind configuration
     */
    public function __construct($config = null, \Bsz\Config\Client $client, \Bsz\Holding $holding)
    {
        parent::__construct($config);
        $this->client = $client;
        $this->holding = $holding;
        $this->localIsils = $this->client->getIsilAvailability();        
    }

    /**
     * Get the CSS class used to properly render a format.  (Note that this may
     * not be used by every theme).
     *
     * @param string $format Format text to convert into CSS class
     *
     * @return string
     */
    public function getFormatClass($format)
    {
        if (is_array($format)) {
            $format = implode(' ', $format);
        }
        return $this->renderTemplate(
                        'format-class.phtml', ['format' => $format]
        );
    }

    /**
     * Render the link of the specified type.
     *
     * @param string $type    Link type
     * @param string $lookfor String to search for at link
     *
     * @return string
     */
    public function getLink($type, $lookfor, $searchClassId = 'Search')
    {
        if ($searchClassId == 'Solr' || $searchClassId == null) {
            $searchClassId = 'Search';
        }
        return $this->renderTemplate(
                        'link-' . $type . '.phtml', ['lookfor' => $lookfor, 'searchClassId' => $searchClassId]
        );
    }

    /**
     * Generate a thumbnail URL (return false if unsupported).
     *
     * @param string $size Size of thumbnail (small, medium or large -- small is
     * default).
     *
     * @return string|bool
     */
    public function getThumbnail($size = 'small')
    {
        // Try to build thumbnail:
        $thumb = $this->driver->tryMethod('getThumbnail', [$size]);

        // No thumbnail?  Return false:
        if (empty($thumb) || array_key_exists('issn', $thumb)) {
            return false;
        }

        // Array?  It's parameters to send to the cover generator:
        if (is_array($thumb)) {
            $urlHelper = $this->getView()->plugin('url');
            return $urlHelper('cover-show') . '?' . http_build_query($thumb);
        }

        // Default case -- return fixed string:
        return $thumb;
    }

    /**
     * Determin if an item is available locally
     * 
     * @param $webservice = false
     * 
     * @return boolean
     */
    public function isAtCurrentLibrary($webservice = false)
    {
        $status = false;        
        if (count($this->ppns) == 0) {
            // if we have local holdings, item can't be ordered
            if ($this->hasLocalHoldings()) {
                $status = true;
            } elseif ($webservice && $this->driver->getNetwork() == 'SWB'
                 && $this->hasParallelEditions()
            ) {
                $status = true;
            } elseif ($webservice && $this->driver->getNetwork() !== 'SWB'
                && $this->queryWebservice()
            ) {
                $status = true;
            }            
        } 
        if ($this->hasLocalHoldings() && $this->driver->getNetwork() == 'ZDB') {
            $this->queryWebservice();
        }
        // we dont't want to do the query twice, so we save the status
        $this->atCurrentLibrary = $status;
        return $status;

    }

    /**
     * Check if the item should have an ill button
     * @return boolean
     */
    public function isAvailableForInterlending()
    {
        // items marked as free
        $f856 = $this->driver->getFieldsArray([856 => 'z']);
        $ppn = $this->driver->getPPN();
        if (($this->driver->getNetWork() == 'HEBIS' && preg_match('/^8/', $ppn))
                || $this->driver->isFree()) {
            return false;
        }

        // printed journals - show hint
        if ($this->driver->isArticle() || $this->driver->isJournal() || $this->driver->isNewspaper()) {
            return true;
        } 
        // ebooks - always available
        else if ($this->driver->isEBook() && $this->driver->getNetwork() == 'SWB') {
            // evaluate ill indicator
            $f924 = $this->driver->tryMethod('getField924');
            foreach ($f924 as $field) {
                if (isset($field['d']) && ($field['d'] == 'e' 
                       || $field['d'] == 'b'
                       // k is deprecated but might still be used
                       || $field['d'] == 'k') ) {
                    return true;
                }
            }
            // eBooks from other networks do not have 924 and can't be ordered. 
            return false;
        }
        // Books - always available, serials are excluded because of Gesamtaufnahmen. 
        else if (!$this->isAtCurrentLibrary(true) &&!$this->driver->isSerial() && !$this->driver->isCollection()) {
            return true;
        }
        return false;
    }

    /**
     * Renders FIS Logo with link
     * @return string
     */
    public function getFisLink()
    {
        return $this->renderTemplate('fis.phtml');
    }

    /**
     * Query webservice to get SWB hits with the same
     * <ul>
     * <li>ISSN or ISBN (preferred)</li>
     * <li>Title, author and year (optional)</li>
     * </ul>
     * Found PPNs are added to ppns array and can be accessed by other methods. 
     *  
     * @return boolean
     */
    protected function queryWebservice()
    {         

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
                            $this->ppns[] = '(DE-576)'.$ppn;
                            $this->libraries[] = $entry['isil'];                                        
                        }  

                    }
                }                    
            }
            // if no locally available ppn found, just take the first one
            if (count($this->ppns) < 1 && isset($result['holdings'])) {
                reset($result['holdings']);
                $this->ppns[] = '(DE-576)'.key($result['holdings']);
            }

        }     
        
        // check if any of the isils from webservic matches local isils
        if (is_array($this->libraries) && count($this->libraries) > 0) {
            return true;
        } 
        return false;
    }
    
    /**
     * Simply checks if there are local holdings available in field 924
     * 
     * @return boolean
     */
    protected function hasLocalHoldings()
    {

        // First, simple checks using fiels 924
        $localHoldings = $this->driver->tryMethod('getLocalHoldings');
        if (count($localHoldings) > 0) {
            return true;
        }
        return false; 

    }
    
    /**
     * Quer< solr for parallel Editions available at local libraries
     * Save the found PPNs in global array
     * 
     * @return boolean
     */
    protected function hasParallelEditions() 
    {
        $ppns = [];
        $related = $this->driver->tryMethod('getRelatedEditions');
        $hasParallel = false;

        foreach ($related as $rel) {
            $ppns[] = $rel['id'];
        }
        $parallel = [];
        if (count($ppns) > 0) {
            $parallel = $this->holding->getParallelEditions($ppns, $this->client->getIsilAvailability());            
            // check the found records for local available isils            
            $isils = [];
            foreach ($parallel->getResults() as $record) {   
                $f924 = $record->getField924(true);
                $recordIsils = array_keys($f924);
                $isils = array_merge($isils, $recordIsils);                
            }
            foreach ($isils as $isil) {
                if (in_array($isil, $this->localIsils)) {
                    $hasParallel = true;
                    $this->ppns[] = $record->getUniqueId();                    
                }
            }            
        }        
        return $hasParallel;
    }
    
    /**
     * Determine if a record is available at the first ISIL or at it's 
     * institutes. In opposite to isAtCurrentLibrary, we do not include other 
     * libraries (=other ISILs) here. 
     * @param string $isil
     */
    public function isAtFirstIsil() {
        
        $holdings = $this->driver->tryMethod('getLocalHoldings');
        $allIsils = $this->client->getIsilAvailability();
        $firstIsil = reset($allIsils);
        
        foreach ($holdings as $holding) {
                if (preg_match("/(^$firstIsil\$)|($firstIsil)[-\/\s]+/", $holding['b'])) {
                return true;
            }
        }
        return false;       
    }
    
    /**
     * Feturn found SWB IDs
     * 
     * @return array
     */
    public function getSwbId()
    {
        return array_unique($this->ppns);
    }
    
    /**
     * Do we have a SWB PPN
     * 
     * @return boolean
     */
    public function hasSwbId() 
    {
        return count($this->ppns) > 0;
    }


    /**
     * Render a sub record to be displayed in a search result list.
     *
     * @author <dku@outermedia.de>
     * @return string the rendered sub record
     */
    public function getSubRecord() {

        return $this->renderTemplate('result-list.phtml');
    }

    
}
