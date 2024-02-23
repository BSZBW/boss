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

/**
 * Description of EDS
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class EDS extends \VuFind\RecordDriver\EDS
{
    /**
     * Get the publication type of the record.
     *
     * @return string
     */
    public function getPubType()
    {
        $type = $this->fields['Header']['PubType'] ?? '';
        return $type;
    }

    public function getFormats()
    {
        $formats = parent::getFormats();
        return $formats;
    }

    /**
     * Get the items of the record.
     *
     * @param null $context
     * @param null $labelFilter
     * @param null $groupFilter
     * @param null $nameFilter *
     *
* @return array
     */
    public function getItems(
        $context = null,
        $labelFilter = null,
        $groupFilter = null,
        $nameFilter = null)
    {
        $items = [];
        if (isset($this->fields['Items']) && !empty($this->fields['Items'])) {
//         \Bsz\Debug::Dump($this->fields);
            foreach ($this->fields['Items'] as $item) {
                $items[] = [
                    'Label' => $item['Label'] ?? '',
                    'Group' => $item['Group'] ?? '',
                    'Data'  => isset($item['Data']) && isset($item['Group']) ?
                        $this->toHTML($item['Data'], $item['Group']) : ''
                ];
            }
        }
        return $items;
    }

    /**
     * Get the full text of the record.
     *
     * @return string
     */
    public function getHTMLFullText()
    {
        return (isset($this->fields['FullText']) &&
                isset($this->fields['FullText']['Text']) &&
                isset($this->fields['FullText']['Text']['Value'])) ?
        $this->toHTML($this->fields['FullText']['Text']['Value']) : '';
    }

    /**
     * Get the full text availability of the record.
     *
     * @return bool
     */
    public function hasHTMLFullTextAvailable()
    {
        return (isset($this->fields['FullText']) &&
                isset($this->fields['FullText']['Text']) &&
                isset($this->fields['FullText']['Text']['Availability']) &&
                '1' == $this->fields['FullText']['Text']['Availability']) ?
                true : false;
    }

    /**
     * Get the PDF availability of the record.
     *
     * @return bool
     */
    public function hasPdfAvailable()
    {
        if (isset($this->fields['FullText'])
            && isset($this->fields['FullText']['Links'])
        ) {
            foreach ($this->fields['FullText']['Links'] as $link) {
                if (isset($link['Type']) && 'pdflink' == $link['Type']) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Performs a regex and replaces any url's with links containing themselves
     * as the text
     *
     * @param string $string String to process
     *
     * @return string        HTML string
     */
    public function linkUrls($string)
    {
        /**
         * "/\b(https?):\/\/([-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|]*)\b/i",
         *       'return "<a href=\'".($matches[0])."\'>".($matches[0])."</a>";'
         **/
        $linkedString = preg_replace_callback(
            "/\b(https?):\/\/(dx\.)?([-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|]*)\b/i",
            function ($matches) {
                $class = "external";
                return "<a class='$class' href='" . ($matches[0]) . "'>" .
                        htmlentities($matches[0]) . "</a>";
            },
            $string
        );
        return $linkedString;
    }

    /**
     * Get the OpenURL parameters to represent this record for COinS even if
     * supportsOpenUrl() is false for this RecordDriver.
     *
     * @return string OpenURL parameters.
     */
    public function getCoinsOpenUrl()
    {
        $params = $this->getOpenUrl($this->supportsCoinsOpenUrl());
        return $params;
    }

    /**
     * parses Format to OpenURL genre
     * @return string
     */
    protected function getOpenURLFormat()
    {
        $ptype = $this->getPubType();
        if (strpos(strtolower($ptype), 'journal') !== false) {
            $formats = ['Journal'];
        } elseif (strpos(strtolower($ptype), 'book') !== false) {
            $formats = ['Book'];
        } elseif (strpos(strtolower($ptype), 'articles') !== false) {
            $formats = ['Article'];
        } else {
            $formats = ['UnknownFormat'];
        }
        return ucfirst(array_shift($formats));
    }

    /**
     * Get the PDF url of the record. If missing, return false
     *
     * @return string
     */
    public function getPdfLink()
    {
        if (isset($this->fields['FullText']['Links'])) {
            foreach ($this->fields['FullText']['Links'] as $link) {
                if (isset($link['Type'])
                    && in_array($link['Type'], $this->pdfTypes)
                    && isset($link['Url'])
                ) {
                    return $link['Url']; // return PDF link
                }
            }
        }
        return false;
    }

    /**
     * Indicate whether export is disabled for a particular format.
     *
     * @param string $format Export format
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function exportDisabled($format)
    {
        // export templates needs to need changed for EDS support
        return strtolower($format) != 'ris';
    }

    /**
     *
     * @param array $arrayKeys Key path to the needed value
     * @param int $level only used for recursion
     * @param array $fields only used for recursion
     * @return array|string
     */
    public function getFieldRecursive($arrayKeys, $level = 0, $fields = null)
    {
        if (!$fields) {
            $fields = $this->fields;
        }
        if (isset($fields[$arrayKeys[$level]])) {
            $newFields = $fields[$arrayKeys[$level]];
            $level++;
            if ($level < count($arrayKeys)) {
                return $this->getFieldRecursive($arrayKeys, $level, $newFields);
            } else {
                // end of recursion
                return $newFields;
            }
        }
        return '';
    }

    /**
     *
     * @return string
     */
    public function getContainerTitle()
    {
        $arrayKeys = [
            'RecordInfo',
            'BibRecord',
            'BibRelationships',
            'IsPartOfRelationships',
            0,
            'BibEntity',
            'Titles',
            0,
            'TitleFull'
        ];
        return $this->getFieldRecursive($arrayKeys);
    }

    /**
     *
     * @return string
     */
    public function getContainerIssue()
    {
        $arrayKeys = [
            'RecordInfo',
            'BibRecord',
            'BibRelationships',
            'IsPartOfRelationships',
            0,
            'BibEntity',
            'Numbering',
        ];

        $numbering = $this->getFieldRecursive($arrayKeys);
        if (is_array($numbering)) {
            foreach ($numbering as $key => $data) {
                if (isset($data['Type']) && strtolower($data['Type']) == 'issue') {
                    return $data['Value'] ?? '';
                }
            }
        }
        return '';
    }

    /**
     *
     * @return string
     */
    public function getContainerVolume()
    {
        $arrayKeys = [
            'RecordInfo',
            'BibRecord',
            'BibRelationships',
            'IsPartOfRelationships',
            0,
            'BibEntity',
            'Numbering',
        ];

        $numbering = $this->getFieldRecursive($arrayKeys);
        if (is_array($numbering)) {
            foreach ($numbering as $key => $data) {
                if (isset($data['Type']) && strtolower($data['Type']) == 'volume') {
                    return $data['Value'] ?? '';
                }
            }
        }
        return '';
    }

    /**
     *
     * @return array
     */
    public function getISSNs() : array
    {
        $issns = parent::getIssns();
        $arrayKeys = [
            'RecordInfo',
            'BibRecord',
            'BibRelationships',
            'IsPartOfRelationships',
            0,
            'BibEntity',
            'Identifiers',
        ];

        $identifiers = $this->getFieldRecursive($arrayKeys);
        if (is_array($identifiers)) {
            foreach ($identifiers as $key => $data) {
                if (isset($data['Type']) && isset($data['Value']) &&
                        strtolower($data['Type']) == 'issn-print') {
                    $issns[] = $data['Value'];
                }
            }
        }
        return $issns;
    }

    /**
     *
     * @return string
     */
    public function getContainerYear()
    {
        $arrayKeys = [
            'RecordInfo',
            'BibRecord',
            'BibRelationships',
            'IsPartOfRelationships',
            0,
            'BibEntity',
            'Dates',
            '0',
            'Y'
        ];
        return $this->getFieldRecursive($arrayKeys);
    }

    /**
     *
     * @return string
     */
    public function getContainerPages()
    {
        $arrayKeys = [
            'RecordInfo',
            'BibRecord',
            'BibEntity',
            'PhysicalDescription',
            'Pagination',

        ];
        $pages = '';
        $pagination = $this->getFieldRecursive($arrayKeys);
        if (isset($pagination['StartPage'])) {
            $pages = $pagination['StartPage'];
        }
//        if (isset($pagination['PageCount'])) {
//            $pages .= ', '.$pagination['PageCount']. 'S';
//        }
        return $pages;
    }

    /**
     *
     * @return string
     */
    public function getDoi()
    {
        $arrayKeys = [
            'RecordInfo',
            'BibRecord',
            'BibEntity',
            'Identifiers',
            '0',
            'Value'
        ];
        $doi =  $this->getFieldRecursive($arrayKeys);
        return $doi;
    }
}
