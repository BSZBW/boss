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

    use AdvancedMarcReaderTrait;
    /**
     * @param string $type all, main_topic, partial_aspect
     *
     * @return array
     * @throws File_MARC_Exception
     *
     */
    public function getFivSubjects(string $type = 'all'): array
    {
        $notationList = [];

        $ind2 = null;
        if ($type === 'main_topics') {
            $ind2 = 0;
        } elseif ($type === 'partial_aspects') {
            $ind2 = 1;
        }
        foreach ($this->getFields('938') as $field) {
            if(!is_array($field)) {
                continue;
            }

            $suba = $this->getSubfield($field, 'a');
            foreach ($this->getSubfields($field, '2') as $sub2) {

                if (!empty($suba) && $field['i1'] == 1
                    && (empty($sub2) || $sub2->getData() != 'gnd')
                    && ((isset($ind2) && $field['i2'] == $ind2) || !isset($ind2))
                ) {
                    $data = preg_replace('/!.*!|:/i', '', $suba);
                    $notationList[] = $data;
                }
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

        foreach ($this->getFields('936') as $field) {
            if(!is_array($field)) {
                continue;
            }
            $suba = $this->getSubField($field, 'a');
            $sub2 = $this->getSubfield($field,'2');
            if (!empty($suba) && !empty($sub2) && $field['i1'] == 'f'
                && $field['i2'] == 'i'
            ) {
                if (preg_match('/^fiv[rs]/', $sub2)) {
                    $data = preg_replace('/!.*!|:/i', '', $suba);
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

        foreach ($this->getFields('936') as $field) {
            if(!is_array($field)) {
                continue;
            }
            $suba = $this->getSubField($field, 'a');
            $sub2 = $this->getSubField($field, '2');
            if ($suba && $sub2 && $field['i1'] == 'f' && $field['i2'] == 'i') {
                if (preg_match('/^fivw/', $sub2)) {
                    $data = preg_replace('/!.*!|:/i', '', $suba);
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
    public function getDFISubject()
    {
        $subjectList = [];
        $arrsub = [];

        foreach ($this->getFields('982') as $field) {
            if(!is_array($field)) {
                continue;
            }
            $suba = $this->getSubField($field, 'a');
            $subx = $this->getSubfield($field, 'x');
            if ($suba && $subx) {
                if ($subx == 'DE-Lg3') {
                    $subjectList[] = $suba;
                }
            }
        }
        // handle recurring subfields - convert them to array
        foreach ($subjectList as $k => $sub) {
            if (strpos($sub, '; ')) {
                $split = explode('; ', $sub);
                $arrsub[$k] = $split;
            } else {
                $arrsub[$k] = $sub;
            }
        }
        return $arrsub;
    }
}


