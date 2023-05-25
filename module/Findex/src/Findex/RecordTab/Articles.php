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

use VuFind\Search\Solr\Results;

class Articles extends \Bsz\RecordTab\Articles
{
    /**
     *
     * @return array|null
     */
    public function getContent()
    {
        if ($this->results === null) {
            $relId = $this->driver->tryMethod('getIdsRelatedArticle');
            $relId[] = $this->driver->getUniqueId();
            $this->results = [];
            if (is_array($relId) && count($relId) > 0) {
                foreach ($relId as $k => $id) {
                    $relId[$k] = 'id:"' . $id . '"';
                }

                $params = [
                    'sort' => 'publishDateDaySort_date desc, id desc',
                    'lookfor' => implode(' OR ', $relId),
                    'limit' => 500
                ];

                $filter = [];
                if ($this->isFL() === false) {
                    foreach ($this->isils as $isil) {
                        $filter[] = '~collection_details:ISIL_' . $isil;
                    }
                }
                //$filter[] = 'material_content_type:Article';
                $params['filter'] = $filter;
                $results = $this->runner->run($params);

                $results instanceof Results;
                $this->results = $results->getResults();
            }
        }
        return $this->results;
    }

}