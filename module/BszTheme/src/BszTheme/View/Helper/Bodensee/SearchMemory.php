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
    public function getLastSearchterms(bool $isHome = false)
    {
        if($isHome) {
            return null;
        }
        $url = $this->memory->retrieveSearch();
        $query_str = parse_url($url, PHP_URL_QUERY);
        parse_str($query_str, $queryArray);
        return $queryArray['lookfor'] ?? null;
    }

    public function getLastSearchLink($link, $prefix = '', $suffix = '')
    {
        $lastLink = parent::getLastSearchLink($link, $prefix, $suffix);
        if($lastLink == '') {
            $urlHelper = $this->getView()->plugin('url');
            $lastLink = $urlHelper('home');
        }
        return $lastLink;
    }

    public function getHiddenFilterString(string $searchClassId): string
    {
        $hiddenFilters = $this->getLastHiddenFilters($searchClassId);
        $hiddenFilterString = '';
        $escapeHtmlAttr = $this->getView()->plugin('escapeHtmlAttr');
        foreach ($hiddenFilters as $key => $filter) {
            foreach ($filter as $value) {
                $hiddenFilterString .= '&hiddenFilters[]=' . $escapeHtmlAttr($key) . ':' . $escapeHtmlAttr($value);
            }
        }
        return $hiddenFilterString;
    }
}
