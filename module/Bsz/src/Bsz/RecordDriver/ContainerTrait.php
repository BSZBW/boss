<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bsz\RecordDriver;

use Bsz\Exception;

/**
 * Trait for all the Container methods
 * @author amzar
 */
trait ContainerTrait
{
    /**
     * As out fiels 773 does not contain any further title information we need
     * to query solr again
     *
     * @return array
     * @throws Exception
     */
    public function getContainer()
    {
        if (count($this->container) == 0 &&
            ($this->isArticle() || $this->isPart())
        ) {
            $relId = $this->getContainerIds();

            $this->container = [];
            if (is_array($relId) && count($relId) > 0) {
                foreach ($relId as $k => $id) {
                    $relId[$k] = 'id:"' . $id . '"';
                }
                $params = [
                    'lookfor' => implode(' OR ', $relId),
                ];
                if (null === $this->runner) {
                    throw new Exception('Please attach a search runner first');
                }
                $results = $this->runner->run($params, 'Solr');
                $this->container = $results->getResults();
            }
        }
        return $this->container;
    }

    /**
     *
     * @return array
     */
    public function getContainerIds()
    {
        $fields = [
            773 => ['w'],
        ];
        $ids = [];
        $array = $this->getFieldsArray($fields);
        foreach ($array as $subfields) {
            $tmp = explode(' ', $subfields);
            foreach ($tmp as $id) {
                // match all PPNs except old SWB PPNs and ZDB-IDs
                if ( !preg_match('/^\(DE-576|DE-600\)/', $id)) {
                    $ids[] = $id;
                }
            }
        }
        return array_unique($ids);
    }

    /**
     *
     * @return string
     */
    public function getContainerId()
    {
        $ids = $this->getContainerIds();
        return array_shift($ids);
    }

    /**
     * Returns ISXN of containing item. ISBN is preferred, if set.
     * @return string
     */
    public function getContainerIsxn()
    {
        $fields = [
            773 => ['z'],
            773 => ['x'],
        ];
        $array = $this->getFieldsArray($fields);
        return array_shift($array);
    }

    /**
     * Returns ISXN of containing item. ISBN is preferred, if set.
     * @return string
     */
    public function getContainerRelParts()
    {
        $fields = [
            773 => ['g'],
        ];
        $array = $this->getFieldsArray($fields);
        return array_shift($array);
    }

    /**
     * This function is used to distinguish between articles from journals
     * and articles from books.
     * @return boolean
     */
    public function isContainerMonography()
    {
        // this is applicable only if item is a part of another item
        if ($this->isPart()) {
            $isxn = $this->getContainerIsxn();
            // isbn set
            if (strlen($isxn) > 9) {
                return true;
            } elseif (empty($isxn)) {
                $containers = $this->getContainer();

                if (is_array($containers)) {
                    $container = array_shift($containers);
                    return isset($container) ? $container->isPhysicalBook() : false;
                }
            }
        }
        return false;
    }

    /**
     * For Articles: get container title
     * @return type
     */
    public function getContainerTitle()
    {
        $fields = [
            773 => ['a', 't'], //SWB, GBV
        ];
        $array = $this->getFieldsArray($fields);
        $title = array_shift($array);
        return str_replace(
            'In: ',
            '',
            $title ?? ''
        );
    }

    /**
     * Get the Container issue from different fields
     * @return string
     */
    public function getContainerIssue()
    {
        $fields = [
            936 => ['e'],
            953 => ['e'],
            773 => ['g']
        ];
        $issue = $this->getFieldsArray($fields);
        if (count($issue) > 0 && !empty($issue[0])) {
            $string = array_shift($issue);
            return $string;
        }
        return '';
    }

    /**
     * Get container pages from different fields
     * @return string
     */
    public function getContainerPages()
    {
        $fields = [
            936 => ['h'],
            953 => ['h'],
            773 => ['t'] // bad data, mixed into title field
        ];
        $pages = $this->getFieldsArray($fields);
        foreach ($pages as $k => $page) {
            preg_match('/\d+ *-? *\d*/', $page, $tmp);
            if (isset($tmp[0]) && $tmp[0] != '-') {
                $pages[$k] = $tmp[0];
            } else {
                unset($pages[$k]);
            }
        }
        return array_shift($pages);
    }

    /**
     * get container year from different fields
     * @return string
     */
    public function getContainerYear()
    {
        $fields = [
            260 => ['c'],
            936 => ['j'],
            363 => ['i'],
            773 => ['t', 'd']
        ];

        $years = $this->getFieldsArray($fields);
        foreach ($years as $k => $year) {
            preg_match('/\d{4}/', $year, $tmp);
            if (isset($tmp[0])) {
                $years[$k] = $tmp[0];
            } else {
                unset($years[$k]);
            }
        }
        return array_shift($years);
    }

    /**
     * This method returns dirty data, don't use it except for ILL!
     */
    public function getContainerRaw()
    {
        $f773g = $this->getFieldArray(773, ['g']);
        return array_shift($f773g);
    }
}
