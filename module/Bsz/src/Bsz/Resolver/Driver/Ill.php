<?php

namespace Bsz\Resolver\Driver;

/**
 * Description of Ill
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Ill extends Ezb
{
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
            'rft.place' => 'place',
            'rft.title' => 'title',
            'rft.atitle' => 'atitle',
            'rft.btitle' => 'title',            
            'rft.jtitle' => 'title',
            'rft.au' => 'aulast',
            'rft.date' => 'date',
            'rft.format' => false,
            'pid' => 'pid',
            'sid' => 'sid',
        ];
        foreach ($params as $key => $value) {
            if (isset($mapping[$key]) && $mapping[$key] !== false) {
                $newParams[$mapping[$key]] = $value;
            }
        }
        if (isset($params['rft.series'])) {
            $newParams['title'] = $params['rft.series'].': '
                    .$newParams['title'];
        }
        
        # UB Heidelbergs implementation differs from default. 
        switch ($newParams['genre']) {
            case 'book': $newParams['genre'] = 'bookitem';
                break;
        }
        $params = [];
        foreach (array_filter($newParams) as $param => $val) {
            $params[] = $param.'='.$val;
        }         
        return implode('&', $params);
    }
}
