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

    public function log($level, $msg, array $context = [])
    {
        if ($this->hasLogger()) {
            $this->logger->$level($this->addLoggerPrefix($msg), $context);
        }
    }

    public function hasLogger()
    {
        return isset($this->logger);
    }

    public function setLoggerPrefix(string $prefix)
    {
        $this->loggerPrefix = $prefix;
    }

    private function addLoggerPrefix(string $msg): string
    {
        return ($this->hasLoggerPrefix() ? '[' . $this->getLoggerPrefix() . '] ' : '') . $msg;
    }

    private function hasLoggerPrefix(): bool
    {
        return !empty($this->loggerPrefix);
    }

    private function getLoggerPrefix(): string
    {
        return $this->loggerPrefix;
    }

    public function alert($msg, array $context = [])
    {
        $this->log('alert', $msg, $context);
    }

    public function critical($msg, array $context = [])
    {
        $this->log('critical', $msg, $context);
    }

    public function debug($msg, array $context = [])
    {
        $this->log('debug', $msg, $context);
    }

    public function emergency($msg, array $context = [])
    {
        $this->log('emergency', $msg, $context);
    }

    public function error($msg, array $context = [])
    {
        $this->log('error', $msg, $context);
    }

    public function info($msg, array $context = [])
    {
        $this->log('info(', $msg, $context);
    }

    public function notice($msg, array $context = [])
    {
        $this->log('notice', $msg, $context);
    }

    public function warning($msg, array $context = [])
    {
        $this->log('warning', $msg, $context);
    }
}