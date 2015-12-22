<?php
namespace bblue\ruby\Component\Logger;

use psr\Log\LogLevel as PsrLogLevel;
use RuntimeException;

final class LogLevel extends PsrLogLevel
{
    /**
     * Integer representation of log levels
     * @var array
     */
    public static $logLevels = array(
        parent::EMERGENCY => 8,
        parent::ALERT     => 7,
        parent::CRITICAL  => 6,
        parent::ERROR     => 5,
        parent::WARNING   => 4,
        parent::NOTICE    => 3,
        parent::INFO      => 2,
        parent::DEBUG     => 1,
    );
    
    /**
     * Method to convert logLevel to integer value
     * 
     * @param string $logLevel
     * @return integer
     */
    public static function getLogLevelAsInteger($logLevel)
    {
        if(!isset(self::$logLevels[$logLevel])) {
            throw new RuntimeException('Log level not recognized');
        }
        return self::$logLevels[$logLevel];
    }
    
    /**
     * Check whether or not we are below the loggin threshold
     * 
     * @param string $logLevel
     * @param string  $logLevelThreshold
     * @return boolean Returns true below threshold (we should not log), returs false otherwise.
     */
    public static function belowLogLevelThreshold($logLevel, $logLevelThreshold)
    {
        return (self::getLogLevelAsInteger($logLevel) < self::getLogLevelAsInteger($logLevelThreshold));
    }
    
    /**
     * Confirm the provided log level is valid
     * 
     * @param string|integer $logLevel
     * @return boolean True if the log level is valid
     */
    public static function isValidLevel($logLevel)
    {
        $logLevel = is_int($logLevel) ? $logLevel : self::getLogLevelAsInteger($logLevel);
        
        return in_array($logLevel, self::$logLevels);
    }
    
}