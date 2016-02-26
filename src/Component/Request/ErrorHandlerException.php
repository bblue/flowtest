<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 21.02.2016
 * Time: 11:48
 */

namespace bblue\ruby\Component\Request;

final class ErrorHandlerException extends \RuntimeException
{
    private $errorRequest;

    public function __construct($message, $code, \Throwable $previous, iInternalErrorRequest $errorRequest)
    {
        $this->errorRequest = $errorRequest;
        parent::__construct($message, $code, $previous);
    }

    public function getRequest(): iInternalErrorRequest
    {
        return $this->errorRequest;
    }
}