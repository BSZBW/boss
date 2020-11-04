<?php

/**
 * Resolver for EZB links
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
namespace Bsz\Resolver\Driver;

class Ezb extends \VuFind\Resolver\Driver\Ezb
{
    protected $pid;

    /**
     * Constructor
     *
     * @param string            $baseUrl    Base URL for link resolver
     * @param \Zend\Http\Client $httpClient HTTP client
     */
    public function __construct($baseUrl, \Zend\Http\Client $httpClient, $pid = '')
    {
        parent::__construct($baseUrl, $httpClient);
        $this->pid = $pid;
    }

    /**
     * Get Resolver Url
     *
     * Transform the OpenURL as needed to get a working link to the resolver.
     *
     * @param string $openURL openURL (url-encoded)
     *
     * @return string Link
     */
    public function getResolverUrl($openURL)
    {
        // Unfortunately the EZB-API only allows OpenURL V0.1 and
        // breaks when sending a non expected parameter (like an ISBN).
        // So we do have to 'downgrade' the OpenURL-String from V1.0 to V0.1
        // and exclude all parameters that are not compliant with the EZB.

        // Parse OpenURL into associative array:
        $tmp = explode('&', $openURL);
        $parsed = [];

        foreach ($tmp as $current) {
            $tmp2 = explode('=', $current, 2);
            $parsed[$tmp2[0]] = $tmp2[1];
        }

        // Downgrade 1.0 to 0.1
        if ($parsed['ctx_ver'] == 'Z39.88-2004') {
            $openURL = $this->downgradeOpenUrl($parsed);
        }

        $openURL .= '&sid=bsz:zdb&pid=' . urlencode($this->pid);

        // Make the call to the EZB and load results
        $url = $this->baseUrl . '?' . $openURL;

        return $url;
    }

    public function getResolverImageParams($params)
    {
        $tmp = explode('&', $params);
        $parsed = [];

        foreach ($tmp as $current) {
            $tmp2 = explode('=', $current, 2);
            $parsed[$tmp2[0]] = $tmp2[1];
        }

        // Downgrade 1.0 to 0.1
        if ($parsed['ctx_ver'] == 'Z39.88-2004') {
            $openURL = $this->downgradeOpenUrl($parsed);
        }
        $openURL .= '&sid=bsz:zdb&pid=' . urlencode($this->pid);

        // Make the call to the EZB and load results
        $paramstring = $openURL;

        return $paramstring;
    }

    /**
     * Downgrade an OpenURL from v1.0 to v0.1 for compatibility with EZB.
     *
     * @param array $params Array of parameters parsed from the OpenURL.
     *
     * @return string       EZB-compatible v0.1 OpenURL
     */
    protected function downgradeOpenUrl($params)
    {
        $newParams = [];
        $mapping = [
            'rft_val_fmt' => false,
            'rft.genre' => 'genre',
            'rft.issn' => 'issn',
            'rft.isbn' => 'isbn',
            'rft.volume' => 'volume',
            'rft.issue' => 'issue',
            'rft.spage' => 'spage',
            'rft.epage' => 'epage',
            'rft.pages' => 'pages',
            //'rft.place' => 'place',
            'rft.title' => 'title',
            'rft.atitle' => 'atitle',
            'rft.btitle' => 'title',
            'rft.jtitle' => 'title',
            'rft.au' => 'aulast',
            'rft.date' => 'date',
            'rft.format' => false,
//            'sid' => 'sid',
//            'rfr_id' => 'sid'
        ];
        foreach ($params as $key => $value) {
            if (isset($mapping[$key]) && $mapping[$key] !== false) {
                $newParams[$mapping[$key]] = $value;
            }
        }
        if (isset($params['rft.series'])) {
            $newParams['title'] = $params['rft.series'] . ': '
                    . $newParams['title'];
        }

//        // for the open url ill form, we need genre = bookitem
//        if ($this->area == 'illform' && $newParams['genre'] == 'article'
//                && $this->recordDriver->isContainerMonography()) {
//            $newParams['genre'] = 'bookitem';
//        }

        // JOP has a really limited amount of allowed genres
        $allowedJopGenres = ['article', 'journal'];
        if (!in_array($newParams['genre'], $allowedJopGenres)) {
            switch ($newParams['genre']) {
                case 'issue': $newParams['genre'] = 'journal';
                    break;
                case 'proceeding': $newParams['genre'] = 'journal';
                    break;
                case 'conference': $newParams['genre'] = 'journal';
                    break;
                // no support for books
                case 'book': return '';
                    break;
                // articles are more probably
                default: $newParams['genre'] = 'article';

            }
        }
        return http_build_query($newParams);
    }
}
