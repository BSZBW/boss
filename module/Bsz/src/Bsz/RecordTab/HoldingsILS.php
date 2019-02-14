<?php

/*
 * The MIT License
 *
 * Copyright 2016 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Bsz\RecordTab;

/**
 * Holdings (ILS) tab
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class HoldingsILS extends \VuFind\RecordTab\HoldingsILS
{
    /**
     * Is this tab active?
     *
     * @return bool
     */
    public function isActive()
    {        
        $id = $this->driver->getUniqueID();
        if ($this->catalog) {
            // try DAIA only in case of SWB or K10plus prefix
            if(strpos($id, '(DE-576)') !== false OR strpos($id, '(DE-627)') !== false) {   
                if ($this->catalog->hasHoldings($id)) {
                    return true;
                }
            }
        } elseif ($this->driver->tryMethod('hasLocalHoldings')) {
            return true;
        }
        return false;
    }
    
}
