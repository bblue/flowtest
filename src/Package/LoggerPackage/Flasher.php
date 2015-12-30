<?php

namespace bblue\ruby\Package\LoggerPackage;

use psr\Log\AbstractLogger;
use bblue\ruby\Component\Core\SessionHandler;

//@todo: Denne lytter til events og registerer om meldingene faktisk ble vist

final class Flasher extends AbstractLogger
{
    /**
     * Minimum logging threshold
     * @var integer
     */
    private $logLevelThreshold = LogLevel::INFO;
   
    public function __construct(SessionHandler $session)
    {
        $this->setLogLevelThreshold($logLevelThreshold);
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
        echo "<pre>[{$level}] {$message}</pre>";
    }
    
    
    /**
     * Sets the Log Level Threshold
     */
    public function setLogLevelThreshold($logLevelThreshold)
    {
        $this->logLevelThreshold = $logLevelThreshold;
    }
}