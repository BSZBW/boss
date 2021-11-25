<?php

/*
 * Copyright (C) 2015 Bibliotheks-Service Zentrum, Konstanz, Germany
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
 */
namespace Bsz\Search\Params;

use Bsz\Search\Solr\Params;
use Interop\Container\ContainerInterface;

/**
 * BSz Search params Factory
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Factory
{
    /**
     * Factory for Solr params object.
     *
     * @param ContainerInterface $container
     *
     * @return \VuFind\Search\Solr\Params
     */
    public static function getSolr(ContainerInterface $container)
    {
        $config = $container->get('VuFind\Config');
        $options = $container->get('VuFind\SearchOptionsPluginManager')->get('solr');
        $dedup = $container->get('VuFindResultsGrouping\Config\Grouping');
        $params = new Params($options, $config, null, $dedup);

        return $params;
    }
}
