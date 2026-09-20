<?php
/**
 * Functions to add advanced MARC-driven functionality to a record driver already
 * powered by the standard index spec. Depends upon MarcReaderTrait.
 *
 * PHP version 7
 *
 * Copyright (C) Villanova University 2017.
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @author   Ere Maijala <ere.maijala@helsinki.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
namespace Bsz\RecordDriver;

/**
 * Functions to add advanced MARC-driven functionality to a record driver already
 * powered by the standard index spec. Depends upon MarcReaderTrait.
 *
 * @category VuFind
 * @package  RecordDrivers
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://vufind.org/wiki/development:plugins:record_drivers Wiki
 */
trait MarcAdvancedTraitBsz
{
    /**
     * Returns the array element for the 'getAllRecordLinks' method
     *
     * @param File_MARC_Data_Field $field Field to examine
     *
     * @return array|bool                 Array on success, boolean false if no
     * valid link could be found in the data.
     */
    protected function getFieldData($field)
    {
        // Make sure that there is a t field to be displayed:
        if ($title = $field->getSubfield('t')) {
            $title = $title->getData();
        } else {
            return false;
        }

        $linkTypeSetting = isset($this->mainConfig->Record->marc_links_link_types)
            ? $this->mainConfig->Record->marc_links_link_types
            : 'id,oclc,dlc,isbn,issn,title';
        $linkTypes = explode(',', $linkTypeSetting);
        $linkFields = $field->getSubfields('w');

        // Run through the link types specified in the config.
        // For each type, check field for reference
        // If reference found, exit loop and go straight to end
        // If no reference found, check the next link type instead
        foreach ($linkTypes as $linkType) {
            switch (trim($linkType)) {
            case 'oclc':
                foreach ($linkFields as $current) {
                    if ($oclc = $this->getIdFromLinkingField($current, 'OCoLC')) {
                        $link = ['type' => 'oclc', 'value' => $oclc];
                    }
                }
                break;
            case 'dlc':
                foreach ($linkFields as $current) {
                    if ($dlc = $this->getIdFromLinkingField($current, 'DLC', true)) {
                        $link = ['type' => 'dlc', 'value' => $dlc];
                    }
                }
                break;
            case 'id':
                foreach ($linkFields as $current) {
                    if ($bibLink = $this->getIdFromLinkingField($current)) {
                        $link = ['type' => 'bib', 'value' => $bibLink];
                    }
                }
                break;
            case 'isbn':
                if ($isbn = $field->getSubfield('z')) {
                    $link = [
                        'type' => 'isn', 'value' => trim($isbn->getData()),
                        'exclude' => $this->getUniqueId()
                    ];
                }
                break;
            case 'issn':
                if ($issn = $field->getSubfield('x')) {
                    $link = [
                        'type' => 'isn', 'value' => trim($issn->getData()),
                        'exclude' => $this->getUniqueId()
                    ];
                }
                break;
            case 'title':
                $link = ['type' => 'title', 'value' => $title];
                break;
            }
            // Exit loop if we have a link
            if (isset($link)) {
                break;
            }
        }
        // Make sure we have something to display:
        return !isset($link) ? false : [
            'title' => $this->getRecordLinkNote($field),
            'value' => $title,
            'link'  => $link,
            'edition' => $this->getRecordEdition($field)
        ];
    }

    public function getRecordEdition($field)
    {
        $edition = '';
        if ($subfield = $field->getSubfield('d')) {
            $edition = trim($subfield->getData());
        }
        return $edition;
    }
}
