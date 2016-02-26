<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 20.02.2016
 * Time: 21:19
 */

namespace bblue\ruby\Component\Request;


final class InternalCliRequest extends aInternalRequest implements iCliRequest
{

    public function getClientAddress(): string
    {
        return 'cli';
    }

    public function getRequestType(): string
    {
        return aRequest::$CLI_REQUEST_TYPE;
    }
}