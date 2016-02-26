<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 20.02.2016
 * Time: 20:11
 */

namespace bblue\ruby\Component\Request;


interface iRequestFactory
{
    public static function buildExternalHttpRequest(string  $url): ExternalHttpRequest;
    public static function buildInternalHttpRequest($get = null, $post = null, $cookie = null, $files = null, $server
    = null): iInternalRequest;
    public static function buildInternalCliRequest(): InternalCliRequest;
    public static function buildInternalErrorRequest(\Throwable $t, iRequest $previousRequest = null):
    iInternalErrorRequest;
}