<?php
namespace Bsz\Config;

use Laminas\Session\Container as SessionContainer;

/**
 * Class for setting dedup options
 *
 * @author Cornelius Amzar <cornelius.amzar@bsz-bw.de>
 */
class Dedup
{
    protected $config;
    /**
     *
     * @var Container
     */
    protected $container;
    protected $response;
    protected $cookiedata;

    public function __construct($config, SessionContainer $container,
            $response, $cookiedata)
    {
        $this->config = $config;
        $this->container = $container;
        $this->response = $response;
        $this->cookie = $cookiedata;
        $this->restoreFromCookie();
    }

    public function restoreFromCookie()
    {
        if (isset($this->cookie->group)) {
            $this->container->offsetSet('group', $this->cookie->group);
        }
        if (isset($this->cookie->group_field)) {
            $this->container->offsetSet('group_field', $this->cookie->group_field);
        }
        if (isset($this->cookie->group_limit)) {
            $this->container->offsetSet('group_limit', $this->cookie->group_limit);
        }
    }

    public function store($post)
    {
        $params = $this->getCurrentSettings();
        $secure = getenv('VUFIND_ENV') === 'production' ? true : false;
        if (array_key_exists('group', $post)) {
            $cookie = new \Laminas\Http\Header\SetCookie(
                    'group',
                    (int)$post['group'],
                    time() + 14 * 24 * 60 * 60,
                    '/');
            $header = $this->response->getHeaders();
            $header->addHeader($cookie);
            $this->container->offsetSet('group', $post['group']);
            $params['group'] = $post['group'];
        }
        if (isset($post['group_field'])) {
            $cookie = new \Laminas\Http\Header\SetCookie(
                    'group_field',
                    $post['group_field'],
                    time() + 14 * 24 * 60 * 60,
                    '/',
                    null,
                    $secure);
            $header = $this->response->getHeaders();
            $header->addHeader($cookie);
            $this->container->offsetSet('group_field', $post['group_field']);
            $params['field'] = $post['group_field'];
        }
        if (isset($post['group_limit'])) {
            $cookie = new \Laminas\Http\Header\SetCookie(
                    'group_limit',
                    $post['group_limit'],
                    time() + 14 * 24 * 60 * 60,
                    '/',
                    null,
                    $secure);
            $header = $this->response->getHeaders();
            $header->addHeader($cookie);
            $this->container->offsetSet('group_limit', $post['group_limit']);
            $params['limit'] = $post['group_limit'];
        }
        return $params;
    }

    public function getCurrentSettings()
    {
        $params = [
           'group' => $this->container->offsetExists('group') ? (bool)$this->container->offsetGet('group') : (bool)$this->config->get('group'),
           'group_field' => $this->container->offsetExists('group_field') ? $this->container->offsetGet('group_field') : $this->config->get('group.field'),
           'group_limit' => $this->container->offsetExists('group_limit') ? $this->container->offsetGet('group_limit') : $this->config->get('group.limit'),
        ];
        return $params;
    }

    public function isActive()
    {
        $conf = $this->getCurrentSettings();
        return $conf['group'] == 1;
    }
}
