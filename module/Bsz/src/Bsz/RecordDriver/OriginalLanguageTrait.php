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
namespace Bsz\RecordDriver;

use VuFind\RecordDriver\Response\PublicationDetails;

trait OriginalLanguageTrait
{
    use AdvancedMarcReaderTrait;

    /**
     * @return string
     */
    public function getTitleOl(): string
    {
        $tmp = [
            $this->getShortTitleOl(),
            ' : ',
            $this->getSubtitleOl(),
        ];
        $title = implode(' ', $tmp);
        return $this->cleanString($title);
    }

    /**
     * @return string
     */
    public function getShortTitleOl(): string
    {
        return $this->getOriginalLanguage(245, 'a');
    }

    /**
     * @return string
     */
    public function getSubtitleOl(): string
    {
        return $this->getOriginalLanguage(245, 'b');
    }

    public function getTitleSectionOl(): string
    {
        $array = $this->getOriginalLanguageArray(['245' => ['n', 'p']]);
        return !empty($array) ? array_shift($array) : '';
    }

    /**
     * GRetrieve the original language string for a given field anf subfield
     *
     * @param $targetField
     * @param $targetSubfield
     * @return string
     */
    public function getOriginalLanguage($targetField, $targetSubfield): string
    {
        $return = '';
        $fields = $this->getFields('880');

        foreach ($fields as $field) {
            if(!is_array($field)) {
                continue;
            }
            $sf6 = $this->getSubfield($field, '6');
            $data = trim($this->getSubfield($field, $targetSubfield));
            if (substr_count($sf6, $targetField) > 0 && !empty($data)) {
                $return = $data;
            }
        }
        return $return;
    }

    /**
     * Get multiple fields at once, subfields are separated by ' ' by default
     * @param array $targets
     * @param string $separator
     * @return array
     */
    public function getOriginalLanguageArray(array $targets, string $separator = ' '): array
    {
        $return = [];
        foreach ($targets as $tag => $subfields) {
            $returnSub = [];
            if (is_array($subfields)) {
                foreach ($subfields as $subfield) {
                    $returnSub[] = $this->getOriginalLanguage($tag, $subfield);
                }
            } else {
                $returnSub[] = $this->getOriginalLanguage($tag, $subfields);
            }

            $tmp = implode($separator, $returnSub);
            $return[] = trim($tmp);
        }
        return array_values(array_filter($return));
    }

    /**
     * @return array
     */
    public function getPlacesOfPublicationOl()
    {
        $fields = [
            260 => 'a',
            264 => 'a',
        ];
        return $this->getOriginalLanguageArray($fields);
    }

    /**
     * Get the publishers of the record.
     *
     * @return array
     */
    public function getPublishersOl(): array
    {
        $fields = [
            260 => 'b',
            264 => 'b',
        ];
        return $this->getOriginalLanguageArray($fields);
    }

    /**
     * Get an array of publication detail lines combining information from
     * getPublicationDates(), getPublishers() and getPlacesOfPublication().
     *
     * @return array
     */
    public function getPublicationDetailsOl(): array
    {
        $places = $this->getPlacesOfPublicationOl();
        $names = $this->getPublishersOl();
        $dates = $this->getHumanReadablePublicationDates();

        // Do not return year only
        if (empty($names) && empty($places)) {
            return [];
        }

        $i = 0;
        $retval = [];

        while (isset($places[$i]) || isset($names[$i]) || isset($dates[$i])) {
            // Build objects to represent each set of data; these will
            // transform seamlessly into strings in the view layer.
            $retval[] = new PublicationDetails(
                $places[$i] ?? '',
                $names[$i] ?? '',
                $dates[$i] ?? ''
            );
            $i++;
        }
        return $retval;
    }

    /**
     * Get container title in original language
     *
     * @return string
     */
    public function getSeriesOl(): array
    {
        $fields = [
            '440' => ['a', 'p'],
            '800' => ['a', 'b', 'c', 'd', 'f', 'p', 'q', 't'],
            '830' => ['a', 'p'],
            '490' => 'a'
        ];
        return $this->getOriginalLanguageArray($fields);
    }

    /**
     * @return string
     */
    public function getEditionOl()
    {
        return $this->getOriginalLanguage(250, 'a');
    }

    /**
     * @return array
     */
    public function getPhysicalDescriptionsOl()
    {
        return $this->getOriginalLanguageArray(['300' => ['a', 'b', 'c', 'e', 'f', 'g']]);
    }
}
