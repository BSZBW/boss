<?php
/*
 * Copyright 2022 (C) Bibliotheksservice-Zentrum Baden-
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
namespace Bsz\RecordDriver;

trait FivTrait {
    /**
     * @param string $type all, main_topic, partial_aspect
     *
     * @return array
     * @throws File_MARC_Exception
     *
     */
    public function getFivSubjects($type = 'all')
    {
        $notationList = [];

        $ind2 = null;
        if ($type === 'main_topics') {
            $ind2 = 0;
        } elseif ($type === 'partial_aspects') {
            $ind2 = 1;
        }

        foreach ($this->getMarcRecord()->getFields('938') as $field) {
            $suba = $field->getSubField('a');
            $sub2 = $field->getSubfield(2);
            if ($suba && $field->getIndicator(1) == 1
                && (empty($sub2) || $sub2->getData() != 'gnd')
                && ((isset($ind2) && $field->getIndicator(2) == $ind2) || !isset($ind2))
            ) {
                $data = $suba->getData();
                $data = preg_replace('/!.*!|:/i', '', $data);
                $notationList[] = $data;
            }
        }
        return $notationList;
    }
    /**
     * Get an array with FIV classification
     * @returns array
     */
    public function getFIVClassification()
    {
        $classificationList = [];

        foreach ($this->getMarcRecord()->getFields('936') as $field) {
            $suba = $field->getSubField('a');
            $sub2 = $field->getSubfield('2');
            if ($suba && $sub2 && $field->getIndicator(1) == 'f'
                && $field->getIndicator(2) == 'i'
            ) {
                $sub2data = $field->getSubfield('2')->getData();
                if (preg_match('/^fiv[rs]/', $sub2data)) {
                    $data = $suba->getData();
                    $data = preg_replace('/!.*!|:/i', '', $data);
                    $classificationList[] = $data;
                }
            }
        }
        return array_unique($classificationList);
    }
    /**
     * Get an array with FIV classification
     * @returns array
     */
    public function getDFIClassification()
    {
        $classificationList = [];

        foreach ($this->getMarcRecord()->getFields('936') as $field) {
            $suba = $field->getSubField('a');
            $sub2 = $field->getSubfield('2');
            if ($suba && $sub2 && $field->getIndicator(1) == 'f'
                && $field->getIndicator(2) == 'i'
            ) {
                $sub2data = $field->getSubfield('2')->getData();
                if (preg_match('/^fivw/', $sub2data)) {
                    $data = $suba->getData();
                    $data = preg_replace('/!.*!|:/i', '', $data);
                    $classificationList[] = $data;
                }
            }
        }
        return array_unique($classificationList);
    }
}


