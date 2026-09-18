<?php
/*
 * Copyright 2021 (C) Bibliotheksservice-Zentrum Baden-
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
 * along with this program; if not, see
 * <https://www.gnu.org/licenses/>.
 *
 */

namespace Bsz\RecordDriver;

class Summon extends \VuFind\RecordDriver\Summon
{
    /**
     * Get an array of all the formats associated with the record.
     *
     * @return array
     */
    public function getFormats()
    {
        $sumfor = isset($this->fields['ContentType'])
            ? $this->fields['ContentType'] : [];
        $sumfor = array_shift($sumfor);
        $mappings = [
            'eBook' => ['Book', 'Online'],
        ];
        return array_key_exists($sumfor, $mappings) ? $mappings[$sumfor] : [$sumfor];
    }
}
