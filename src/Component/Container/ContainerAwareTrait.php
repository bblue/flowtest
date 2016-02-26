<?php

namespace bblue\ruby\Component\Container;

trait ContainerAwareTrait
{
    /**
     * Instance of the Container class
     * @var Container
     */
    public $container; //@todo: Make unpublic 

    /**
     * Method to assign the container class
     * @param Container $container
     */
    public function setContainer(Container $container) 
    {
        $this->container = $container;
    }
    
    public function hasContainer()
    { 
        return isset($this->container);
    }
}