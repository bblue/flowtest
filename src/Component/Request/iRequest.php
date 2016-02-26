<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 17:36
 */

namespace bblue\ruby\Component\Request;

interface iRequest
{
    public function setAddress(string $address);
    public function getAddress(): string;
    public function getRequestType(): string;
    public function getRequestStartTimestamp(): float;
    public function setRequestStartTimestamp(float $timestamp);
    public function getExecutionTime(): int;
    public function isCliRequest(): bool;
    public function isHttpRequest(): bool;
}