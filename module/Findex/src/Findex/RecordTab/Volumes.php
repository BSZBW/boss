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

namespace Findex\RecordTab;

class Volumes extends \Bsz\RecordTab\Volumes
{
    /**
     *
     * @return array|null
     */
    public function getContent()
    {
        if ($this->content === null) {
            $relId = $this->driver->tryMethod('getIdsRelated');
            // add the ID of the current hit, thats usefull if its a
            // Gesamtaufnahme
            $this->content = [];
            if (is_array($relId)) {
                array_push($relId, $this->driver->getUniqueID());
                if (is_array($relId) && count($relId) > 0) {
                    foreach ($relId as $k => $id) {
//                      $relId[$k] = 'id_related_host_item:"'.$id.'"';
                        $relId[$k] = 'id:"' . $id . '"';
                    }
                    $params = [
                        'sort' => 'publishDateDaySort_date desc, id desc',
                        'lookfor' => implode(' OR ', $relId),
                        'limit'   => 500,
                    ];

                    $filter = [];

                    // Test: all Formats but articles
                    //$filter[] = '-material_content_type:Article';

                    $params['filter'] = $filter;

                    $results = $this->runner->run($params);
                    $this->content = $results->getResults();
                }
            }
        }
        return $this->content;
    }
}