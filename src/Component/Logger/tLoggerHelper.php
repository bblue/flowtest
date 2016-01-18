<?php

/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 18.01.2016
 * Time: 21:05
 */
trait tLoggerHelper
{
    use \bblue\ruby\Component\Logger\LoggerAwareTrait;

    protected function alert($msg, $context)
    {
        $this->log('alert', $msg, $context);
    }

    private function log(string $level, $msg, $context)
    {
        if ($this->hasLogger()) {
            $this->logger->$level($msg, $context);
        }
    }

    private function hasLogger()
    {
        return isset($this->logger);
    }

    protected function critical($msg, $context)
    {
        $this->log('critical', $msg, $context);
    }

    protected function debug($msg, $context)
    {
        $this->log('debug', $msg, $context);
    }

    protected function emergency($msg, $context)
    {
        $this->log('emergency', $msg, $context);
    }

    protected function error($msg, $context)
    {
        $this->log('error', $msg, $context);
    }

    protected function info($msg, $context)
    {
        $this->log('info(', $msg, $context);
    }

    protected function notice($msg, $context)
    {
        $this->log('notice', $msg, $context);
    }

    protected function warning($msg, $context)
    {
        $this->log('warning', $msg, $context);
    }
}