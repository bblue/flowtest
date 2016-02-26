<?php

namespace bblue\ruby\Package\LoggerPackage;

use bblue\ruby\Component\Core\iAdapterImplementation;
use bblue\ruby\Component\Logger\iLogLevelThreshold;
use bblue\ruby\Component\Logger\LogLevel;
use psr\Log\AbstractLogger;

final class EchoLogger extends AbstractLogger implements iLogLevelThreshold, iAdapterImplementation
{
    /**
     * Current minimum logging threshold
     * @var integer
     * @todo Denne må flyttes inn interfacet
     */
    private $logLevelThreshold = LogLevel::DEBUG;
   
    private $_cache = array();
    private $_useCache;
    private $_useHTML;
    private $_cli;
    
    /**
     * @todo pass options as array
     * @param unknown $logLevelThreshold
     * @param string $cache
     * @param string $html
     */
    public function __construct($logLevelThreshold, $cache = true, $html = true, $cli = false)
    {
        $this->setLogLevelThreshold($logLevelThreshold);
        $this->_useCache = $cache;
        $this->_useHTML = $html;
        $this->_cli = $cli;
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
        $msg = "[{$level}] {$message}";
        if($this->_useCache) {
            $this->_cache[] = $msg;
        } else {
            $this->_print($msg);
        }
    }
    
    public function __destruct()
    {
        if($this->_useCache) {
            if($this->_useHTML) {
                $this->_print('<pre class="small">' . implode('<br />', $this->_cache) . '</pre>');
            } else {
                $this->_print(implode("\n", $this->_cache));
            }
        }
    }
    
    private function _print($msg)
    {
        if($this->_cli) {
            fwrite(STDOUT, $msg."\n");
        } else {
            echo $msg;
        }
    }
    
    /**
     * Sets the Log Level Threshold
     */
    public function setLogLevelThreshold($logLevelThreshold)
    {
        $this->logLevelThreshold = $logLevelThreshold;
    }
}