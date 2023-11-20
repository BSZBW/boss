<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Bsz\RecordDriver;

/**
 * Description of MarcAuthorTrait
 *
 * @author amzar
 */
trait MarcAuthorTrait
{
    use AdvancedMarcReaderTrait;

    /**
     *
     * @return array
     */
    public function getPrimaryAuthors() : array
    {
        $primary = $this->getFirstFieldValue('100', ['a', 'b']);
        return empty($primary) ? [] : [$primary];
    }

    /**
     *
     * @return array
     */
    public function getPrimaryAuthorsLives() : array
    {
        $primary = $this->getMarcFieldsAuthor('100', ['d']);
        return $primary;
    }

    /**
     *
     * @return array
     */
    public function getPrimaryAuthorsRoles() : array
    {
        $primary = $this->getMarcFieldsAuthor('100', ['4']);
        return $primary;
    }

    /**
     *
     * @return array
     */
    public function getPrimaryAuthorsGnds() : array
    {
        $primary = $this->getMarcFieldsAuthor('100', ['0']);
        foreach ($primary as &$value) {
            $value = preg_replace('/\(.*\)/', '', $value);
        }
        unset($value);
        return $primary;
    }

    /**
     *
     * @return array
     */
    public function getSecondaryAuthors() : array
    {
        return $this->getFieldArray('700', ['a', 'b']);
    }

    /**
     *
     * @return array
     */
    public function getSecondaryAuthorsLives() : array
    {
        $secondary = $this->getMarcFieldsAuthor('700', ['d']);
        return $secondary;
    }

    /**
     *
     * @return array
     */
    public function getSecondaryAuthorsRoles() : array
    {
        $secondary = $this->getMarcFieldsAuthor('700', ['4']);
        return $secondary;
    }

    /**
     *
     * @return array
     */
    public function getSecondaryAuthorsGnds() : array
    {
        $secondary = $this->getMarcFieldsAuthor('700', ['0']);
        foreach ($secondary as &$value) {
            $value = preg_replace('/\(.*\)/', '', $value);
        }
        unset($value);
        return $secondary;
    }

    /**
     *
     * @return array
     */
    public function getCorporateAuthors() : array
    {
        return array_merge(
            $this->getFieldArray('110', ['a', 'b']),
            $this->getFieldArray('111', ['a', 'b']),
            $this->getFieldArray('710', ['a', 'b']),
            $this->getFieldArray('711', ['a', 'b'])
        );
    }

    /**
     *
     * @return array
     */
    public function getCorporateAuthorsRoles() : array
    {
        return array_merge(
            $this->getMarcFieldsAuthor('110', ['4']),
            $this->getMarcFieldsAuthor('111', ['4']),
            $this->getMarcFieldsAuthor('710', ['4']),
            $this->getMarcFieldsAuthor('711', ['4'])
        );
    }

    /**
     * Retur given MARC fields not only those who have the given subfields.
     *
     * @param int $tag
     * @param array $subfields
     * @return array
     */
    public function getMarcFieldsAuthor($tag, $subfields = [])
    {
        $fields = $this->getFields($tag);
        $result = [];

        foreach ($fields as $field) {
            if (!is_array($field)) {
                continue;
            }

            $content = [];
            foreach ($field['subfields'] as $subfield) {
                if (in_array($subfield['code'], $subfields)) {
                    $content[] = $subfield['data'];
                }
            }
            // throw repeated subfields away! They are too difficult to process
            $result[] = array_shift($content);
        }
        return $result;
    }
}
