<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 17:37
 */

namespace bblue\ruby\Component\Request;

interface iInternalRequest extends iRequest
{
    public function getClientAddress(): string;
    public function _server(string $key = null);
    public function _post(string $key = null);
    public function _get(string $key = null);
    public function _env(string $key = null);
}