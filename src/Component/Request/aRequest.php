<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 20.02.2016
 * Time: 20:14
 */

namespace bblue\ruby\Component\Request;

/**
 * Class aRequest
 *
 * Common methods for internal and external requests
 *
 * @package bblue\ruby\Component\Request
 */
abstract class aRequest implements iRequest
{
    static $HTTP_REQUEST_TYPE = 'http';
    static $CLI_REQUEST_TYPE = 'cli';

    protected $startTimestamp;

    /**
     * @var string The target address of the request
     */
    protected $address;

    public function __construct(string $address)
    {
        $this->setRequestStartTimestamp(microtime(true));
        $this->setAddress($address);
    }

    public function setAddress(string $address)
    {
        $this->address = $address;
    }

    public function getAddress(): string
    {
        return $this->address ?? '';
    }

    public function getRequestStartTimestamp(): float
    {
        return $this->startTimestamp;
    }

    public function setRequestStartTimestamp(float $timestamp)
    {
        $this->startTimestamp = $timestamp;
    }

    public function getExecutionTime(): int
    {
        return round(1000*(microtime(true) - $this->getRequestStartTimestamp()), 0);
    }

    public function isCliRequest(): bool
    {
        return ($this->getRequestType() === self::$CLI_REQUEST_TYPE);
    }

    public function isHttpRequest(): bool
    {
        return $this->getRequestType() === self::$HTTP_REQUEST_TYPE;
    }
}