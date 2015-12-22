<?php

namespace bblue\ruby\Component\Config;

use psr\Log\LoggerAwareInterface;
use RuntimeException;

abstract class AbstractConfig implements ConfigInterface, LoggerAwareInterface
{
    use \bblue\ruby\Component\Logger\LoggerAwareTrait;
    
    /**
     * Internal array to store all configurationvariables
     * @var unknown
     */
    private $_aConfigParameters = array();
    
    const ENVIRONMENT_DEV = 'dev';
    const ENVIRONMENT_PROD = 'prod';
    const ENVIRONMENT_CLI = 'cli';
    
    public function __construct($sEnvironment = self::ENVIRONMENT_PROD)
    {
        $this->sEnvironment = $sEnvironment;
        
        switch ($sEnvironment) {
            case self::ENVIRONMENT_DEV:
                $aConfig = $this->getDevelopmentEnvironmentParameters();
                break;
            case self::ENVIRONMENT_PROD:
                $aConfig = $this->getProductionEnvironmentParameters();
                break;
            case self::ENVIRONMENT_CLI:
                $aConfig = $this->getCliEnvironment();
                break;
            default:
                throw new RuntimeException('Unable to handle selected environment (' . $sEnvironment . ')');
        }
        $this->addConfigurationParameters($aConfig);
    }
    
    abstract public function getProductionEnvironmentParameters();
    abstract public function getDevelopmentEnvironmentParameters();
    abstract public function getCliEnvironment();
    
    /**
     * Magic method to retrieve configuration variables
     * 
     * @param mixed $var
     * @return mixed|void Returns void if variable is unknown, returns the variable if set
    */
    public function __get($var)
    {
        if (!array_key_exists($var, $this->_aConfigParameters)) {
            $message = 'Trying to retrieve missing config parameter: ' . $var;
            if (isset($this->logger)) {
               $this->logger->debug($message);
            }
            throw new RuntimeException($message);
        }
    
        return $this->_aConfigParameters[$var];
    }
    
    /**
     * Magic method to set configuration variables
     * 
     * @param mixed $var
     * @return self
     */
    public function __set($var, $value)
    {
        $this->_aConfigParameters[$var] = $value;
        return $this;
    }
    
    /**
     * Determines if the app is running in development mode based on the environment variable
     * 
     * @return boolean
     */
    public function isDevMode()
    {
        return ($this->sEnvironment === self::ENVIRONMENT_DEV);
    }
    
    /**
     * Merge configuration array with the storage
     * 
     * @param array $aConfigParameters
     * @return array The merged config parameters
     */
    protected function addConfigurationParameters(array $aConfigParameters)
    {
        return $this->_aConfigParameters = array_merge($this->_aConfigParameters, $aConfigParameters);
    }
    
    public function setDebugMode($mode = true)
    {
        $this->__set('bDebug', (bool)$mode);
        if($mode) {
            $this->logger->info('Enabling debug mode');
            if($this->sEnvironment == self::ENVIRONMENT_PROD) {
                $this->logger->alert('Debug mode enabled for production enviroment!');
            }
        }
    }
    
}