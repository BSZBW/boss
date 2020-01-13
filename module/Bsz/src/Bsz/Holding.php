<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
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
 */

namespace Bsz;
use VuFind\Search\SearchRunner as Runner;

/**
 * class for the BSZ holdings service
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Holding {
    
     /**
     *
     * @var array
     */
    protected $isxns = [];
    /**
     *
     * @var string
     */
    protected $network;
    /**
     *
     * @var int
     */
    protected $year;
    /**
     *
     * @var string
     */
    protected $title;
    /**
     *
     * @var string
     */
    protected $author;
    /**
     *
     * @var string
     */
    protected $zdbId;
    /**
     *
     * @var Runner
     */
    protected $runner;
    
    /**
     *
     * @var bool
     */
    protected $debug;
    
    /**
     * 
     * @param Bsz\Config\Client $client
     */
    public function __construct(Runner $runner) 
    {
        $this->runner = $runner;
    }
    /**
     * Set ISBNs 
     * 
     * @param string $isxns
     * 
     * @return \Bsz\Holding
     */
    public function setIsxns($isxns) 
    {
        if (!is_array($isxns)) {
            $isxns = (array)$isxns;
        }
        foreach ($isxns as $isxn) {
            // strip non numeric chars
            $isxn = preg_replace('/[^0-9]/', '', $isxn);
            if (strlen($isxn) > 0 && is_numeric($isxn)) {
                $this->isxns[] = $isxn;
            }
        }
        $this->isxns = array_unique($this->isxns);
        return $this;
    }
    
    /**
     * Set title
     * 
     * @param string $title
     * 
     * @return Bsz\Holding
     */
    public function setTitle($title)
    {
        $this->title = urldecode($title);
        
        return $this;
    }
    
    /**
     * Set primary author
     * 
     * @param string $authos
     * 
     * @return Bsz\Holding
     */
    public function setAuthor($authos) 
    {
        $this->author = urldecode($authos);
        
        return $this;
    }
/**
 * Set Network
 * 
 * @param string $network SWB|GBV|KOBV|...
 * 
 * @return \Bsz\Holding
 */
    public function setNetwork($network) 
    {
        $this->network = strtoupper($network);

        return $this;
    }
    
    /**
     * Set Year
     * 
     * @param type $year
     * 
     * @return Bsz\Holding
     */
    public function setYear($year) 
    {
        if ((int)$year > 1800) {
            $this->year = (int)$year;
        }
        return $this;
    }
    /**
     * Set ZDB ID for good journal search results
     * 
     * @param string $zdb
     * 
     * @return $this
     */
    public function setZdbId($zdb)
    {
        if (!empty($zdb)) {
            $this->zdbId = $zdb;
        }
        return $this;
    }
    
    
    /**
     * Query solr
     * 
     * @return array
     */
    public function query() 
    {
        $orString = '';
        $and = [];
        $params = [];
        if (isset($this->network)) {
            $params['filter'] = 'consortium:' . $this->network;
        }        
        if (!empty($this->title)) {
            $and[] = 'title:"'.$this->title.'"';
        }
        if (!empty($this->author)) {
            $and[] = 'author:"'.$this->author.'"';
        }
        if (!empty($this->year)) {
            $and[] = 'publish_date:'.$this->year;
        }
        if (!empty($this->zdbId)) {
            $and[] = 'zdb_id:'.$this->zdbId;
        }
                
        if (count($this->isxns) > 0) {
            $or = [];
            foreach($this->isxns as $isxn) {
                if(strlen($isxn) <= 9) {
                    $or[] = 'issn:' . $isxn;
                } elseif (strlen($isxn) > 9) {
                    $or[] = 'isbn:' . $isxn;
                }
            }
            $orString = implode(' OR ',$or);
            // add braces to orString if there are ands set
            $and[] = count($and) > 0 ? '(' . $orString . ')' : $orString;
        }
        $params['lookfor'] = implode(' AND ', $and);        

        $results = $this->runner->run($params, 'Solr');
        $results instanceof \Bsz\Search\Solr\Results;
        
        return $this->parse($results);
    }
    /**
     * process the response
     * 
     * @param \Zend\Http\Response $response
     */
    public function parse(\VuFind\Search\Solr\Results $results) 
    {
        $return = [];
        if ($results->getResultTotal() > 0) {
            
            foreach ($results->getResults() as $record) {
                $libraries = [];
                $record instanceof \Bsz\RecordDriver\SolrGviMarc;
                $ppn = $record->getPPN();
                $f924 = $record->getField924(true, true);
                
                // iterate through all found 924 entries
                // ISILs are unified here - information is being dropped! 
                foreach ($f924 as $isil => $field) {
                    $libraries[] = [
                        'isil' => $isil,
                        'callnumber' => isset($field['g']) ? $field['g'] : '',
                        'issue' => isset($field['z']) ? $field['z'] : ''
                    ];
                }

                //Catch Errors in parsing
                if ($libraries === null) {
                    continue;
                }
                $return['holdings'][$ppn] = $libraries;
            }
            $return['numppn'] = count($return['holdings']);
            $return['numfound'] = count($libraries);

        }
        else {
            $return['numfound'] = 0;
        }
        return $return;

    }
    /**
     * Checks if all needed params are set. 
     * 
     * @return boolean
     */
    public function checkQuery() 
    { 
        if (isset($this->network)) {
            if (count($this->isxns) > 0 || isset($this->zdbId)) {
                return true;
            } else if (!empty($this->title) && !empty($this->author)) {
                return true;
            }            
        }
        return false;
        
    }
    /**
     * Check whether parallel editions exist
     * 
     * @param array $ppns
     * @param array $isil
     * s
     * @return array
     */
    public function getParallelEditions($ppns, $isils) 
    {
        $params = [];

        foreach ($ppns as $k => $ppn) {
            // escape braces 
            $ppns[$k] = 'id:'.str_replace(['(', ')'], ['\(', '\)'], $ppn);
        }
        $orLookfor = implode(' OR ', $ppns);
        $lookfor[] = $orLookfor;

        $params['lookfor'] = $lookfor;
        $params['wt'] = 'json';

        $results = $this->runner->run($params, 'Solr');
        return $results;       

    } 
  
}
