<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace BszTheme\View\Helper\Bodensee;

/**
 * Description of SearchMemory
 *
 * @author amzar
 */
class SearchMemory extends \VuFind\View\Helper\Root\SearchMemory
{
    /**
     * Use this instead of getLastSearchLink if you don't want any markup.
     *
     * @return string
     */
    public function getLastSearchUrl()
    {
        $last = $this->memory->retrieveSearch();
        if (!empty($last)) {
            $escaper = $this->getView()->plugin('escapeHtml');
            return $escaper($last);
        }
        return '';
    }

    /**
     *  get searchterms from session
     *
     * @return string
     */
    public function getLastSearchterms()
    {
        $url = $this->memory->retrieveSearch();
        $query_str = parse_url($url, PHP_URL_QUERY);
        parse_str($query_str, $queryArray);
        return $queryArray['lookfor'] ?? null;
    }
}
