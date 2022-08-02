<?php
namespace BszTheme\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * String View Helper functions
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class StringHelper extends AbstractHelper
{
    public function __invoke()
    {
        return $this;
    }

    /**
     * Removes unwanted chars from string
     * Usage (in view) $this->string()->clean()
     * @param string $string
     * @return string
     */
    public function cleanEsc($string)
    {
        $string = $this->view->escapeHtml($string);
        $string = trim($string);
        $string = preg_replace('/:$|\/$|,$/', '', $string);
        $string = trim($string);
        return $string;
    }

    /**
     * Truncate strings
     * On words it searches a suitable whitespace, on URLS it cuts off the
     * exact position.
     * @param string $string
     * @param int $length
     * @return string
     */
    public function shorten($string, $length = 30)
    {
        $return = $string;
        if (strlen($string) > $length) {
            //if String is an URL, we can'look for word ends
            if (strpos($string, 'http') !== false) {
                $return = substr($string, 0, $length) . '&hellip;';
            } else {
                $return = substr($string, 0, strrpos(substr($string, 0, $length), ' ')) . '&hellip;';
            }
        }
        return $return;
    }

    /**
     * Ebsco Data sometime contains escaped HTML chars.
     * These are un-escaped here and returned as HTML.
     * @param string $string
     * @return string
     */
    public function cleanEbscoHtml($string)
    {
        $output = '';
        $replace = [
            '<ephtml>' => '',
            '</ephtml>' => '',
        ];
        $output = str_replace(array_keys($replace), $replace, $string);
        return html_entity_decode($output);
    }

    public function cleanEbscoLinks($dirty)
    {
        $clean = trim(strip_tags($dirty));
        // cleaned link contains multiple links
        $pos = strpos($clean, 'http', 3);
        if ($pos >= 5) {
            $clean = substr($clean, 0, $pos);
        }
        $parts = parse_url($clean);
        if ($parts !== false) {
            if (isset($parts['scheme']) && isset($parts['host'])) {
                $parts['scheme'] = $parts['scheme'] . '://';
                $parts['query'] = isset($parts['query']) ? '?' . $parts['query'] : '';
                return implode('', $parts);
            } elseif (isset($parts['path'])) {
                return 'https://doi.org/' . $parts['path'];
            }
        } else {
            return '';
        }
    }
}
