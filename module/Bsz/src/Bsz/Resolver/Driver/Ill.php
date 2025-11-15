<?php
namespace Bsz\Resolver\Driver;

/**
 * Description of Ill
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Ill extends Ezb
{
    protected $additions = [];

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
        // Parse OpenURL into associative array:
        $tmp = explode('&', $openURL);
        $parsed = [];

        foreach ($tmp as $current) {
            $tmp2 = explode('=', $current, 2);
            $parsed[$tmp2[0]] = $tmp2[1];
        }
        $parsed['sid'] = 'SWB:flportal';
        $parsed = array_merge($parsed, $this->additions);

        // Downgrade 1.0 to 0.1
        if ($parsed['ctx_ver'] == 'Z39.88-2004') {
            $openURL = $this->downgradeOpenUrl($parsed);
        }

        // Make the call to the EZB and load results
        $url = $this->baseUrl . '?' . $openURL;

        return $url;
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
            'rft.pub' => 'pub',
            'rft.place' => 'place',
            'rft.title' => 'title',
            'rft.series' => 'series',
            'rft.edition' => 'edition',
            'rft.atitle' => 'atitle',
            'rft.btitle' => 'title',
            'rft.jtitle' => 'title',
            'rft.au' => 'aulast',
            'rft.date' => 'date',
            'rft.format' => false,
            'pid' => false, //pid is removed here, because this historic core is added  in OpenUrl VIEW Helper
            'sid' => 'sid',
        ];
        foreach ($params as $key => $value) {
            if (isset($mapping[$key]) && $mapping[$key] !== false) {
                $newParams[$mapping[$key]] = $value;
            }
        }

        // remove date info for journals because users must choose themselves.
        if ($newParams['genre'] == 'journal') {
            unset($newParams['date']);
        }

        $return = [];
        foreach (array_filter($newParams) as $param => $val) {
            $return[] = $param . '=' . $val;
        }
        return implode('&', $return);
    }
}
