<?php
namespace BszTheme\View\Helper\Bodensee;

use Laminas\View\Helper\Url;

/**
 * BSZ extension of searchTabs View Helper
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class SearchTabs extends \VuFind\View\Helper\Root\SearchTabs
{
    public function isILL($searchClassId = 'Solr')
    {
        $hiddenFilterStr = urldecode($this->getCurrentHiddenFilterParams($searchClassId));
        if (strpos($hiddenFilterStr, 'consortium:FL') !== false ||
                strpos($hiddenFilterStr, 'consortium:ZDB') !== false) {
            return true;
        }
        return false;
    }

    public function isK10plus($searchClassId = 'Solr')
    {
        $hiddenFilterStr = urldecode($this->getCurrentHiddenFilterParams($searchClassId));
        if (strpos($hiddenFilterStr, 'consortium:K10plus')) {
            return true;
        }
        return false;
    }

    public function isZDB($searchClassId = 'Solr')
    {
        $hiddenFilterStr = urldecode($this->getCurrentHiddenFilterParams($searchClassId));
        if (strpos($hiddenFilterStr, 'consortium:ZDB') !== false) {
            return true;
        }
        return false;
    }

    /**
     * Create information representing a selected tab.
     *
     * @param string $id             Tab ID
     * @param string $class          Search class ID
     * @param string $label          Display text for tab
     * @param string $permissionName Name of a permissionrule
     *
     * @return array
     */
    protected function createSelectedTab($id, $class, $label, $permissionName)
    {
        return [
            'id' => $id,
            'class' => $class,
            'icon' => $this->getIcon($id),
            'label' => $label,
            'permission' => $permissionName,
            'selected' => true
        ];
    }

    /**
     * Create information representing a basic search tab.
     *
     * @param string $id             Tab ID
     * @param string $class          Search class ID
     * @param string $label          Display text for tab
     * @param string $newUrl         Target search URL
     * @param string $permissionName Name of a permissionrule
     *
     * @return array
     */
    protected function createBasicTab($id, $class, $label, $newUrl, $permissionName)
    {
        return [
            'id' => $id,
            'class' => $class,
            'icon' => $this->getIcon($id),
            'label' => $label,
            'permission' => $permissionName,
            'selected' => false,
            'url' => $newUrl
        ];
    }

    /**
     * Create information representing a tab linking to "search home."
     *
     * @param string $id             Tab ID
     * @param string $class          Search class ID
     * @param string $label          Display text for tab
     * @param array  $filters        Tab filters
     * @param string $permissionName Name of a permissionrule
     *
     * @return array
     */
    protected function createHomeTab($id, $class, $label, $filters, $permissionName)
    {
        // If an advanced search is available, link there; otherwise, just go
        // to the search home:
        $results = $this->results->get($class);
        $url = $this->url->__invoke($results->getOptions()->getSearchHomeAction())
            . $this->buildUrlHiddenFilters($results, $filters);
        return [
            'id' => $id,
            'class' => $class,
            'icon' => $this->getIcon($id),
            'label' => $label,
            'permission' => $permissionName,
            'selected' => false,
            'url' => $url
        ];
    }

    /**
     * Create information representing an advanced search tab.
     *
     * @param string $id             Tab ID
     * @param string $class          Search class ID
     * @param string $label          Display text for tab
     * @param array  $filters        Tab filters
     * @param string $permissionName Name of a permissionrule
     *
     * @return array
     */
    protected function createAdvancedTab($id, $class, $label, $filters,
        $permissionName
    ) {
        // If an advanced search is available, link there; otherwise, just go
        // to the search home:
        $results = $this->results->get($class);
        $options = $results->getOptions();
        $advSearch = $options->getAdvancedSearchAction();
        $url = $this->url
            ->__invoke($advSearch ? $advSearch : $options->getSearchHomeAction())
            . $this->buildUrlHiddenFilters($results, $filters);
        return [
            'id' => $id,
            'class' => $class,
            'icon' => $this->getIcon($id),
            'label' => $label,
            'permission' => $permissionName,
            'selected' => false,
            'url' => $url
        ];
    }

    /**
     * Get Font Awesome Icon for tab
     * @param string $id
     * @return string
     */
    public static function getIcon($id)
    {
        switch (strtolower($id)) {
            case 'eds': $icon = 'fa-newspaper-o';
                break;
            case 'summon': $icon = 'fa-newspaper-o';
                break;
            case 'solr:filtered1': $icon = 'fa-book';
                break;
            case 'solr:filtered2': $icon = 'fa-newspaper-o';
                break;
            case 'solr': $icon = 'fa-globe';
                break;
            case 'fis': $icon = 'fa-university';
                break;
            default: $icon = 'fa-search';
                break;
        }
        return $icon;
    }
}
