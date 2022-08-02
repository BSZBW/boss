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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

namespace Bsz\Controller;

class CoverController extends \VuFind\Controller\CoverController
{

    /**
     * SDistinguish between show image (VuFind standard)
     * and show base64 encoded image
     * @return \Laminas\Http\Response
     */
    public function showAction()
    {
        $this->sessionSettings->disableWrite(); // avoid session write timing bug

        // Special case: proxy a full URL:
        $url = $this->params()->fromQuery('proxy');
        $base64 = $this->params()->fromQuery('base64');

        if (!empty($url)) {
            try {
                $image = $this->proxy->fetch($url);
                return $this->displayImage(
                    $image->getHeaders()->get('content-type')->getFieldValue(),
                    $image->getContent()
                );
            } catch (\Exception $e) {
                // If an exception occurs, drop through to the standard case
                // to display an image unavailable graphic.
            }
        }

        // Default case -- use image loader:
        $this->loader->loadImage($this->getImageParams());
        if ($base64) {
            return $this->getBase64();
        } else {
            return $this->displayImage();
        }
    }

    /**
     * Support method -- update the view to display the image currently found in the
     * \VuFind\Cover\Loader.
     *
     * @param string $type  Content type of image (null to access loader)
     * @param string $image Image data (null to access loader)
     *
     * @return \Laminas\Http\Response
     */
    protected function getBase64($type = null, $image = null)
    {
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine(
            'Content-type',
            'text/plain'
        );

        // Send proper caching headers so that the user's browser
        // is able to cache the cover images and not have to re-request
        // then on each page load. Default TTL set at 14 days

        $coverImageTtl = (60 * 60 * 24 * 14); // 14 days
        $headers->addHeaderLine(
            'Cache-Control',
            "maxage=" . $coverImageTtl
        );
        $headers->addHeaderLine(
            'Pragma',
            'public'
        );
        $headers->addHeaderLine(
            'Expires',
            gmdate('D, d M Y H:i:s', time() + $coverImageTtl) . ' GMT'
        );
        $data = $image ?: $this->loader->getImage();
        $base64 = base64_encode($data);
        $response->setContent($base64);
        return $response;
    }
}
