<?php

namespace bblue\ruby\Component\Package;

use bblue\ruby\Component\Config\ConfigAwareInterface;
use bblue\ruby\Component\Config\ConfigAwareTrait;
use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Container\ContainerAwareTrait;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
use bblue\ruby\Component\Logger\tLoggerAware;
use psr\Log\LoggerAwareInterface;

/**
 * Extend this class to retrieve a number of useful commands. Specifically the logger, config, container and event dispatcher 
 * 
 * @author Aleksander Lanes
 *
 */
abstract class AbstractPackage implements LoggerAwareInterface, ConfigAwareInterface, ContainerAwareInterface,
    EventDispatcherAwareInterface, iPackage
{
    use tLoggerAware;
    use ContainerAwareTrait;
    use ConfigAwareTrait;
    use EventDispatcherAwareTrait;
     
    /**
     * Variable to define whether or not hte package has booted
     * @var boolean
     */
    private $booted = false;
    
    public function bootPackage()
    {
        if($this->boot()) {
            return $this->isBooted(true);
        }
    }

    abstract public function boot();

    public function isBooted($booted = null)
    {
        if ($booted) {
            $this->booted = (bool)$booted;
        }
        return $this->booted;
    }
    
    /**
     * Obtain the name of the package. Defaults to class name including namespace.
     * @return string Returns the result of get_called_class()
     */
    public function getName()
    {
        return get_called_class();
    }

    /**
     * Method called by Kernel to register controller commands defined by package
     * @todo Rimelig sikker p� at jeg egentlig ikke trenger denne funksjonen siden jeg likevel m� lage et routing map
     * @return boolean Returns true on success, false on error
     */
    public function registerCommands()
    {
        return true;
    }
}