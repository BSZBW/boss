<?php

/*
 * The MIT License
 *
 * Copyright 2017 Cornelius Amzar <cornelius.amzar@bsz-bw.de>.
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
namespace Bsz\Parser;

/**
 * Pasrinsg of OpenURL Params
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class OpenUrl
{
    /**
     * @var \Laminas\Config\Config
     */
    protected $config;
    /**
     * @var array
     */
    protected $params;

    public function __construct(\Laminas\Config\Config $config)
    {
        $this->config = $config;
    }

    /**
     *
     * @param array $params
     * @return $this
     */
    public function setParams(array $params)
    {
        foreach ($params as $k => $value) {
            if (preg_match('/\./', $k)) {
                $newk = str_replace('.', '_', $k);
                $params[$newk] = $value;
                unset($params[$k]);
            }
        }
        $this->params = $params;
        return $this;
    }

    /**
     * Map OpenURL params to ill form field names
     *
     * @return array
     *
     * qthrows Bsz\Exception
     */
    public function map2Form()
    {
        if (null === $this->params) {
            throw new \Bsz\Exception('Use setParams to assign the parameters first.');
        }
        $mappedParams = [];
        foreach ($this->params as $param => $value) {
            $key = $this->map('Form', $param);
            if (!empty($key)) {
                $mappedParams[$key] = urldecode($value);
            }
        }
        if (isset($this->params['rft_genre']) && $this->params['rft_genre'] == 'book') {
            $mappedParams['Verfasser'] = $mappedParams['AufsatzAutor'];
            unset($mappedParams['AufsatzAutor']);
        }
        return $mappedParams;
    }

    /**
     * Map a param from a given section
     *
     * @param string $section
     * @param string $key
     *
     * @return string
     */
    protected function map($section, $key)
    {
        $newKey = '';
        if ($this->config->offsetExists($section)
            && $this->config->get($section)->offsetExists($key)
        ) {
            $newKey = $this->config->get($section)->get($key);
        }
        return $newKey;
    }
}
