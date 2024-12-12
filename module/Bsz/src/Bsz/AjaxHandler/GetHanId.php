<?php

namespace Bsz\AjaxHandler;

use Laminas\Config\Config;
use Laminas\Mvc\Controller\Plugin\Params;
use Laminas\View\Renderer\RendererInterface;
use VuFind\I18n\Translator\TranslatorAwareInterface;

class GetHanId extends \VuFind\AjaxHandler\AbstractBase implements TranslatorAwareInterface
{
    use \VuFind\I18n\Translator\TranslatorAwareTrait;

    /**
     * HTTP client
     *
     * @var \Laminas\Http\Client
     */
    protected $httpClient;

    protected $renderer;

    protected $config;

    public function __construct(
        $httpClient,
        RendererInterface $renderer,
        Config $config)
    {
        $this->httpClient = $httpClient;
        $this->renderer = $renderer;
        $this->config = $config;
    }


    public function handleRequest(Params $params)
    {
        $postParams = $params->fromQuery('params');
        $alternative = $params->fromQuery('alternative');

        $baseUrl = $this->config->get('HanApi')->get('url');
        if (empty($baseUrl)) {
            return $this->formatResponse(
                $this->translate("Could not load info for HAN Api."),
                self::STATUS_HTTP_ERROR
            );
        }

        $raw = $this->doRequest($baseUrl . '/hanapi/action', $postParams);
        $view = $this->getItems($raw, $baseUrl, $alternative);
        $html = $this->renderer->render('ajax/hanLinks.phtml', $view);

        return $this->formatResponse(compact('html'));
    }

    public function doRequest($baseUrl, $params)
    {
        $this->httpClient->setUri($baseUrl);
        $this->httpClient->setMethod('POST');
        $this->httpClient->setHeaders([
                'accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]
        );
        $this->httpClient->setRawBody($params);

        return $this->httpClient->send()->getBody();
    }

    protected function getItems($rawData, $baseUrl, $alternative)
    {
        $array = json_decode($rawData, true);
        $array = $array['scripts'] ?? [];
        $retVal = [];
        foreach ($array as $item) {
            if (!isset($item['fulltext'])) {
                continue;
            }
            $url = $baseUrl . $item['fulltext'];
            $title = $item['title'] ?? $url;
            $licenseInfo = $item['permission'] ?? [];
            $licenseActive = $licenseInfo['active'] ?? false;
            $license = $licenseActive ? ($licenseInfo['description'] ?? null) : null;
            $retVal[] = array_filter(compact('url', 'title', 'license'));
        }
        return [
            'data' => $retVal,
            'alt' => $alternative
        ];
    }

}