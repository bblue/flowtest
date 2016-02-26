<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 20.02.2016
 * Time: 23:24
 */

namespace bblue\ruby\Component\Request;

final class RequestFactory implements iRequestFactory
{

    public static function buildExternalHttpRequest(string $url): ExternalHttpRequest
    {
        return new ExternalHttpRequest($url);
    }

    public static function buildInternalHttpRequest($get = null, $post = null, $cookie = null, $files = null, $server= null): iInternalRequest
    {
        return new InternalHttpRequest($get, $post, $cookie, $files, $server);
    }

    public static function buildInternalCliRequest(): InternalCliRequest
    {
        return new InternalCliRequest();
    }

    public static function buildInternalErrorRequest(\Throwable $t, iRequest $request = null, array $server = []):
    iInternalErrorRequest
    {
        return new InternalErrorRequest($t, $request, $server);
    }
}