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

namespace Bsz\Config;

use \Zend\Db\Sql\Sql,
    \Zend\Db\ResultSet\ResultSet,
    \Zend\Db\TableGateway\TableGateway;


/**
 * Class for reading library config
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Libraries extends TableGateWay
{

    const ID_BAWUE = 1;
    const ID_SAARLAND = 2;
    const ID_SACHSEN = 3;

    protected $libraries = [];
    protected $countries;

    /**
     * Returns libraries for given isil(s)
     * @param array $isils
     * @return ResultSet
     */
    public function getActive($isils)
    {
        if (!$this->compareIsils($isils)) {
            $sql = new Sql($this->getAdapter());
            $select = $sql->select()
                ->from('libraries')
                ->join('authentications', 'fk_auth = authentications.id', ['auth_name' => 'name'])
                ->order('libraries.name')
                ->order('isil');
            $select->where->
                    and
                    ->equalTo('is_ill_active', 1)
                    ->in('isil', $isils);    

            $results = $this->selectWith($select);

            $output = [];

            // Inject Places into Library
            foreach ($results as $result) {
                $places = $this->getPlaces($result->getIsil());
                $result->setPlaces($places);
                $output[] = $result;
            }

            $this->libraries = $output;
        }
        return $this->libraries;
    }

    /**
     * Get a list of libraries by isil
     *
     * @param array $isils
     * @return array
     */
    public function getByIsils($isils) {
        $sql = new Sql($this->getAdapter());
        $select = $sql->select()
                  ->from('libraries')
                  ->join('authentications', 'fk_auth = authentications.id', ['auth_name' => 'name'])
                  ->order('libraries.name')
                  ->order('isil');
        if (count($isils) > 0) {
            $select->where->and->in('isil', $isils);
        }
        return $this->selectWith($select);

    }
    /**
     * Get one Library by ISIL
     *
     * @param string $isil
     * @return Library
     */
    public function getByIsil($isil) {
        $sql = new Sql($this->getAdapter());
        $select = $sql->select()
            ->from('libraries')
            ->join('authentications', 'fk_auth = authentications.id', ['auth_name' => 'name'])
            ->order('libraries.name')
            ->order('isil')
            ->limit(1);
        if (!empty($isil)) {
            $select->where->equalTo('isil', $isil);
            return $this->selectWith($select)->current();
        }
        return null;

    }
    /**
     * For some use-cases, we need to get the first selected library
     * @param array $isils
     * @returns Library
     */
    public function getFirstActive($isils)
    {
        $result = $this->getActive($isils);
        return array_shift($result);
    }

    /**
     *
     * @param int $id
     * @return ResultSet
     */
    public function getByCountryId($id)
    {
        $sql = new Sql($this->getAdapter());
        $select = $sql->select()
                ->from('libraries')
                ->order('name');
        $select->where
                ->and
                    ->equalTo('fk_country', (int)$id)
                    ->equalTo('is_ill_active', 1)
                    ->equalTo('is_boss', 0);

        return $this->selectWith($select);
    }
    /**
     * Get Libraries with Shibboleth IDP entry
     *
     * @param int $id
     *
     * @return ResultSet
     */
    public function getByShib()
    {
        $sql = new Sql($this->getAdapter());
        $select = $sql->select()
                ->from('libraries')
                ->join('authentications', 'fk_auth = authentications.id', ['auth_name' => 'name'])
                ->order('libraries.name');
        $select->where
                ->and
                    ->notEqualTo('shibboleth_idp', '')
                    ->equalTo('is_ill_active', 1);
        return $this->selectWith($select);
    }
    
    public function getByIdPDomain($domain) 
    {
        $sql = new Sql($this->getAdapter());
        $select = $sql->select()
                ->from('libraries')
                ->join('authentications', 'fk_auth = authentications.id', ['auth_name' => 'name'])
                ->order('libraries.name');
        $select->where
                ->and
                    ->equalTo('is_ill_active', 1)
                    ->or
                        ->like('shibboleth_idp', '%'.$domain.'%')
                        ->like('homepage', '%'.$domain.'%');
        return $this->selectWith($select)->current();;
    }

    /**
     * Does any active library have places
     * @param string $isils
     * @return bool
     */
    public function hasActivePlaces($isils)
    {
        if (empty($isils)) {
            return false;
        }
        $sql = new Sql($this->getAdapter());
        $select = $sql->select()
                ->from('places')
                ->order('name');
        $select->where->and
                    ->in('library', $isils)
                    ->equalTo('active', 1);
        $result = $this->selectWith($select);
        return $result->count() > 0 ? true : false;
    }

    /**
     * Fetch Places
     * @param string $singleIsil
     * @return ResultSet
     */
    public function getPlaces($singleIsil)
    {
        // we need another table gateway here as we want to fetch data from
        // another table
        $resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAYOBJECT, new Place());
        $gateway = new TableGateway('places', $this->getAdapter(), null, $resultSetPrototype);

        if (strlen($singleIsil) > 0) {
            $sql = new Sql($gateway->getAdapter());
            $select = $sql->select()
                    ->from('places')
                    ->order('sort', 'name');

            $select->where->AND->equalTo('active', 1)
                               ->equalTo('library', $singleIsil);
            return $gateway->selectWith($select);
        }
        return [];
    }

    /**
     * Compare Isils from existent libraries with requested isils
     * @return boolean
     */
    public function compareIsils($isils)
    {

        if (count($isils) !== count($this->libraries)) {
            return false;
        }
        foreach ($this->libraries as $library) {
            if (!in_array($library->getIsil(), $isils)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Returns all active ill libraries that matches the given name
     * 
     * @param string $name
     * @return array
     */
    public function getActiveByName($name) {
        
        $sql = new Sql($this->getAdapter());
        $select = $sql->select()
            ->from('libraries')
            ->order('libraries.name')
            ->order('name')
            ->limit(20);
        $select->where->
                and
                ->equalTo('is_ill_active', 1)
                ->like('libraries.name', '%'.$name.'%');

        $results = $this->selectWith($select);        
        return $results;
    }
}
