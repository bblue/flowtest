<?php
namespace bblue\ruby\Component\Logger;

use bblue\ruby\Component\Core\iAdapterAware;
use bblue\ruby\Component\Core\iAdapterImplementation;
use psr\Log\LoggerInterface;

class Psr3LoggerHandler implements LoggerInterface, iAdapterAware
{  
    /**
     * Array containing logging adapters
     * @var LoggerInterface
     */ 
    private $aLoggingAdapters = array();
    
    private $bEnabled;
    private $sDefaultThreshold;
    
    /**
     * Array that holds a cache of the log entries
     * @var array
     */
    private $aLogCache = array();

    public function __construct($logLevel = LogLevel::ERROR)
    {
        $this->bEnabled = ($logLevel);
        $this->sDefaultThreshold = $logLevel;
        
        if(!LogLevel::isValidLevel($logLevel)) {
            throw new \UnexpectedValueException('The provided logging level is not valid');
        }
    }

    public function isEnabled()
    {
        return $this->bEnabled;
    }
    

    public function registerAdapter(iAdapterImplementation $adapter, string $identifier = null)
    {
        return $this->registerLoggerAdapter($adapter);
    }

    /**
     * Register any number of psr3 loggers as adapter to this masterclass
     * @param LoggerInterface $adapter   PSR3 compliant logger
     * @param boolean         $bGetCache Determines if the log cache should be loaded into the adapter, this will log all entries prior to the adapter being set. Defaults to true.
     * @param bool|string     $bPrepend  Set to true to prepend the adapter to the start of the adapter array, making sure it is called first. Default to false = end of array
     */
    private function registerLoggerAdapter(LoggerInterface $adapter, $bGetCache = true, $bPrepend = false)
    {
        if ($bPrepend) {
            array_unshift($this->aLoggingAdapters, $adapter);
        } else {
            array_push($this->aLoggingAdapters, $adapter);
        }

        if ($bGetCache) {
            $this->loadCache($adapter);
        }
    }

    /**
     * Load the cache into the adapter
     * 
     * @param LoggerInterface $adapter
     */
    private function loadCache(LoggerInterface $adapter)
    {
    	$adapter->debug('Loading log cache into adapter');
    	foreach($this->aLogCache as $aLogEntry) {
    		$adapter->{$aLogEntry['logLevel']}('[cache] '.$aLogEntry['message'], $aLogEntry['context']);
    	}
    }
    
    /**
     * {@inheritDoc}
     */
    public function emergency($message, array $context = array())
    {
        return $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    
    /**
     * {@inheritDoc}
     */
    public function alert($message, array $context = array())
    {
        return $this->log(LogLevel::ALERT, $message, $context);
    }
    
    /**
     * {@inheritDoc}
     */
    public function critical($message, array $context = array())
    {
        return $this->log(LogLevel::CRITICAL, $message, $context);
    }
    
    /**
     * {@inheritDoc}
     */
    public function error($message, array $context = array())
    {
        return $this->log(LogLevel::ERROR, $message, $context);
    }
    
    /**
     * {@inheritDoc}
     */
    public function warning($message, array $context = array())
    {
        return $this->log(LogLevel::WARNING, $message, $context);
    }
    
    /**
     * {@inheritDoc}
     */
    public function notice($message, array $context = array())
    {
        return $this->log(LogLevel::NOTICE, $message, $context);
    }
    
    /**
     * {@inheritDoc}
     */
    public function info($message, array $context = array())
    {
        return $this->log(LogLevel::INFO, $message, $context);
    }
    
    /**
     * {@inheritDoc}
     */
    public function debug($message, array $context = array())
    {
        return $this->log(LogLevel::DEBUG, $message, $context);
    }
    
    /**
     * Method to pass log item to all adapters
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return mixed Returns the return of each method, but false if logging is disabled
     */
     public function log($logLevel, $message, array $context = array())
     {
     	// confirm logging mechanism is enabled
        if(!$this->bEnabled) {
            return false;
        }
        
        // Save entry to log cache
        $this->saveToCache($logLevel, $message, $context);
        
        // Send the log to each adapter 
        foreach($this->aLoggingAdapters as $adapter) {
             $adapter->$logLevel($message, $context);
         }
     }
     
     private function saveToCache($logLevel, $message, array $context = array())
     {
     	$this->aLogCache[] = array(
     		'logLevel'		=> $logLevel,
     		'message'		=> $message,
     		'context'		=> $context
     	);
     }
}