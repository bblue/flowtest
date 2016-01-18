<?php

/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 18.01.2016
 * Time: 21:05
 */

namespace bblue\ruby\Component\Logger;

trait tLoggerHelper
{
    use tLoggerAware;

    private $loggerPrefix;

    public function alert($msg, $context)
    {
        $this->log('alert', $msg, $context);
    }

    private function log(string $level, $msg, $context)
    {
        if ($this->hasLogger()) {
            $this->logger->$level($this->addLoggerPrefix($msg), $context);
        }
    }

    private function hasLogger()
    {
        return isset($this->logger);
    }

    private function addLoggerPrefix(string $msg): string
    {
        return ($this->hasLoggerPrefix() ? '[' . $this->getLoggerPrefix() . '] ' : '') . $msg;
    }

    private function hasLoggerPrefix(): bool
    {
        return isset($this->loggerPrefix);
    }

    private function getLoggerPrefix(): string
    {
        return $this->loggerPrefix;
    }

    public function critical($msg, $context = null)
    {
        $this->log('critical', $msg, $context);
    }

    public function debug($msg, $context = null)
    {
        $this->log('debug', $msg, $context);
    }

    public function emergency($msg, $context = null)
    {
        $this->log('emergency', $msg, $context);
    }

    public function error($msg, $context = null)
    {
        $this->log('error', $msg, $context);
    }

    public function info($msg, $context = null)
    {
        $this->log('info(', $msg, $context);
    }

    public function notice($msg, $context = null)
    {
        $this->log('notice', $msg, $context);
    }

    public function setLoggerPrefix(string $prefix)
    {
        $this->loggerPrefix = $prefix;
    }

    public function warning($msg, $context = null)
    {
        $this->log('warning', $msg, $context);
    }
}