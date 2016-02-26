<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 20.02.2016
 * Time: 17:49
 */

namespace bblue\ruby\Component\Request;


use bblue\ruby\Component\Validation\Validation;

final class InternalHttpRequest extends aInternalRequest implements iHttpRequest
{
    private $get = [];
    private $post = [];
    private $cookie = [];
    private $files = [];

    /**
     * @var string
     */
    private $url;

    public function __construct(array $get = [], array $post = [], array $cookie = [], array $files =
    [], array $server = [])
    {
        $this->get = $get;
        $this->post = $post;
        $this->cookie = $cookie;
        $this->files = $files;

        parent::__construct($server);
    }

    public function setUrl(string $url)
    {
        // Remove trailing slash
        $url = rtrim($url, '/');
        $this->url = $this->sanitizeUrl($url);
    }

    private function sanitizeUrl(string $url): string
    {
        $validation = new Validation();
        $validation->addSource(array('url' => $url));
        $validation->addValidationRule('url', 'url', true);
        $validation->validate();
        if($validation->hasError()) {
            throw new \Exception($validation->getErrors('url'));
        }
        return $validation->sanitized['url'];
    }

    public function getUrl(): string
    {
        if(!isset($this->url)) {
            $protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
            $this->setUrl($protocol.$this->_server('HTTP_HOST\'').$this->_server('REQUEST_URI'));
        }
        return $this->url;
    }

    public function getClientAddress(): string
    {
        return $this->_server('REMOTE_ADDR') ?: 'undefined';
    }

    public function isAjaxRequest(): boolean
    {
        return ('XMLHttpRequest' == $this->_server('HTTP_X_REQUESTED_WITH'));
    }

    public function getUserAgent(): string
    {
        return $this->_server('HTTP_USER_AGENT');
    }

    /**
     * @param string $key
     * @param string $var
     * @return null
     * @throws \Exception
     */
    private function _http(string $var, string $key = null)
    {
        $var = strtolower($var);
        switch($var) {
            case 'post':
            case 'get':
            case 'cookie':
            case 'files':
            case 'env':
                return $key ? ($this->$var[$key] ?? null) : $this->$var;
            case 'request':
                return $this->post[$key] ?? $this->get[$key] ?? null;
            default:
                throw new \Exception('Incorrect http var provided (' . $key ?? 'null'. ')');
        }
    }

    public function _get(string $key = null)
    {
        return $this->_http('get', $key);
    }

    public function _post(string $key = null)
    {
        return $this->_http('post', $key);
    }

    public function _env(string $key = null)
    {
        return $this->_http('env', $key);
    }

    public function getRequestType(): string
    {
        return aRequest::$HTTP_REQUEST_TYPE;
    }

    public function getRequestStartTimestamp(): float
    {
        return $this->_server('REQUEST_TIME_FLOAT');
    }
}