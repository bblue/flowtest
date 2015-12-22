<?php
namespace bblue\ruby\Component\Config;

use psr\Log\LoggerInterface;

trait ConfigAwareTrait
{
    /**
     * Instance of the configuration class
     * @var ConfigInterface
     */
    protected $config;
    
    /**
     * Method to assign a configuration class
     *
     * @param ConfigInterface $config
     */
    public function setConfig(ConfigInterface $config)
    {
        $this->config = $config;
    }
    
    public function hasConfig()
    {
        return isset($this->config);
    }
}