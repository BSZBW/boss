<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace BszTheme\View\Helper\Bodensee;

use Laminas\View\Helper\AbstractHelper;

/**
 * View Helper for implementing Mapongo maps
 *
 * @author amzar
 */
class Mapongo extends AbstractHelper
{
    /**
     *
     * @var \Laminas\Config\Config
     */
    protected $config;

    public function __construct(\Bsz\Config\Client $config)
    {
        $this->config = $config;
    }

    /**
     * Invoked in the template, returns HTML
     *
     * @param string $signatur
     * @param string $lang
     * @return string
     */
    public function __invoke($signatur, $lang = 'de')
    {
        preg_match('/\|\s(.*)/', $signatur, $matches);
        $rvk = $matches[1] ?? '';
        return $this->render($rvk, $lang);
    }

    /**
     * Renders a helper template
     *
     * @param string $rvk
     * @param string $lang
     * @return string
     */
    protected function render($rvk, $lang)
    {
        $imageurl = $this->config->get('imageurl');
        $linkurl = $this->config->get('url');
        $qrurl = $this->config->get('qrurl');

        if (!empty($linkurl) && !empty($rvk)) {
            $replace = [
                '%SIG%' => urlencode($rvk),
                '%LANG%' => $lang,
            ];
            $imageurl = str_replace(array_keys($replace), $replace, $imageurl);
            $qrurl = str_replace(array_keys($replace), $replace, $qrurl);
            $linkurl = str_replace(array_keys($replace), $replace, $linkurl);

            $params = [
                'imageurl' => $imageurl,
                'qrurl' => $qrurl,
                'linkurl' => $linkurl,
            ];

            $view = $this->getView()->partial('Helpers/mapongo.phtml', $params);
            return $view;
        } else {
            return '';
        }
    }
}
