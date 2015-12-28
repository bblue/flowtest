<?php

namespace bblue\ruby\Component\EventDispatcher;

trait EventDispatcherAwareTrait
{
    /**
     * Instance of the EventDispatcher class
     * @var EventDispatcher
     */
    protected $eventDispatcher; 
    
    /**
     * Method to assign the container class
     * 
     * @param EventDispatcher $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcher $eventDispatcher) 
    {
        $this->eventDispatcher = $eventDispatcher;
    }
    
    public function hasEventDispatcher()
    {
        return isset($this->eventDispatcher);
    }
}