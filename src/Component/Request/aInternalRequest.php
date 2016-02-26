<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 20.02.2016
 * Time: 17:47
 */

namespace bblue\ruby\Component\Request;

/**
 * Class aInternalRequest
 *
 * Implements all internal request methods common for the different subclasses (json, html, etc.)
 *
 * @package bblue\ruby\Component\Request
 */
abstract class aInternalRequest extends aRequest implements iInternalRequest
{
    protected $server = [];

    public function __construct(array $server = array(), string $address = null)
    {
        $this->server = $server;
        parent::__construct($address ?? $this->_server('REQUEST_URI'));
    }

    public function getClientAddress(): string
    {
        return 'undefined';
    }

    public function getUserAgent(): string
    {
        return 'undefined';
    }

    public function _server(string $key = null)
    {
        return $key ? ($this->server[$key] ?? null) : $this->server;
    }

    public function _get(string $key = null)
    {
        // TODO: Implement _get() method.
    }

    public function _env(string $key = null)
    {
        // TODO: Implement _env() method.
    }

    public function _post(string $key = null)
    {
        // TODO: Implement _post() method.
    }
}