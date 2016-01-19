<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 18.01.2016
 * Time: 21:54
 */

namespace bblue\ruby\Component\Logger;

use Psr\Log\{
    LoggerInterface,
    LoggerAwareInterface
};

interface iLoggable extends LoggerInterface, LoggerAwareInterface
{
    public function log($level, $msg, array $context = []);
}