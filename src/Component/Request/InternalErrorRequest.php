<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 20.02.2016
 * Time: 13:16
 */

namespace bblue\ruby\Component\Request;

use bblue\ruby\Component\Router\Router;

final class InternalErrorRequest extends aInternalRequest implements iInternalErrorRequest
{
    /**
     * @var \Throwable
     */
    private $t;
    /**
     * @var iRequest
     */
    private $previousRequest;

    /**
     * @var string
     */
    private $requestType;

    /**
     * InternalErrorRequest constructor.
     * @param \Throwable $t
     * @param iRequest   $previousRequest
     */
    public function __construct(\Throwable $t, iRequest $previousRequest = null, array $server = [])
    {
        $this->t = $t;
        if($this->previousRequest) {
            $this->previousRequest = $previousRequest;
            $this->setRequestType($previousRequest->getRequestType());
        }
        parent::__construct($server, Router::SERVER_500_ERROR_URL);
    }

    /**
     * @return iRequest
     */
    public function getPreviousRequest(): iRequest
    {
        return $this->previousRequest;
    }

    public function hasPreviousRequest(): bool
    {
        return isset($this->previousRequest);
    }

    /**
     * @return \Throwable
     */
    public function getError(): \Throwable
    {
        return $this->t;
    }

    public function getRequestType(): string
    {
        return $this->requestType ?? 'unknown';
    }

    public function setRequestType(string $type)
    {
        $this->requestType = strtolower($type);
    }
}