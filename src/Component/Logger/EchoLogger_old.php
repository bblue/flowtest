<?php

namespace bblue\ruby\Package\LoggerPackage;

use psr\Log\AbstractLogger;
use bblue\ruby\Component\Logger\iLogLevelThreshold;
use bblue\ruby\Component\Logger\LogLevel;

final class EchoLogger extends AbstractLogger implements iLogLevelThreshold
{

    /**
     * Current minimum logging threshold
     * @var integer
     * @todo Denne må flyttes inn interfacet
     */
    private $logLevelThreshold = LogLevel::DEBUG;
   
   
    public function __construct($logLevelThreshold)
    {
        $this->logLevelThreshold = $logLevelThreshold;
    }
    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if (LogLevel::belowLogLevelThreshold($level, $this->logLevelThreshold)) {
            return;
        }
        echo "[{$level}] {$message}";
    }
}