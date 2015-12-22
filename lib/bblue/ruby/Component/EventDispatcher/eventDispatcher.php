<?php

namespace bblue\ruby\Component\EventDispatcher;

use bblue\ruby\Component\Container\ContainerAwareInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use bblue\ruby\Component\Container\Container;

/**
 * @todo bygge inn støtte for event subscribers
 * @author Aleksander Lanes
 *
 */
final class EventDispatcher implements ContainerAwareInterface, LoggerAwareInterface
{
    use \bblue\ruby\Component\Container\ContainerAwareTrait;
    use \bblue\ruby\Component\Logger\LoggerAwareTrait;
    
    /**
     * Array that holds all listener objects
     * @var array
     */
    private $aListeners = array();
    
    public function __construct(LoggerInterface $logger, Container $container)
    {
        $this->setLogger($logger);
        $this->setContainer($container);
    }
    
    public function addListener($sEventIdentifier, $callback)
    {
        $aEventData = $this->extractDataFromEventIdentifier($sEventIdentifier);
        $aEventData['callback'] = function($event, $element) use ($callback) {
        	return call_user_func($callback, $event, $element);
        };
        
        $this->aListeners[] = $aEventData;
    }

    private function extractDataFromEventIdentifier($sEventIdentifier)
    {
    	$aEventPathElements = explode(".", $sEventIdentifier);
    	$sEventName = end($aEventPathElements);
    	$sFirstElement = reset($aEventPathElements);
        return array(
        	'sEventName' => $sEventName,
        	'aEventPathElements' => $aEventPathElements,
            'iEventPathElements' => count($aEventPathElements),
        	'sFirstElement'		=> $sFirstElement
        );
    }

    private function isMatch($aEventData, $aListenerData)
    {
    	if($aListenerData['iEventPathElements'] === 1 && $aListenerData['sEventName'] == $aEventData['sEventName']) {
    		return true;
    	}
    	
		if ($aListenerData['sFirstElement'] == '*') { // Check if string starts with *
    		$aEventData['aEventPathElements'] = array_reverse($aEventData['aEventPathElements']);
    		$aListenerData['aEventPathElements'] = array_reverse($aListenerData['aEventPathElements']);
    	}
    	return $this->checkForMatch($aEventData, $aListenerData);
    }

    private function checkForMatch(array $aEventData, array $aListenerData)
    {
    	foreach($aEventData['aEventPathElements'] as $key => $sElementToMatch) {
    		$sElement = array_slice($aListenerData['aEventPathElements'], $key, 1);
    		if(isset($sElement[0])) {
    			$sElement = $sElement[0];
    		} else {
    			continue;
    		}
    		 
    		if($sElement == '*') {
    			$bMatch = true;
    			continue;
    		} else if ($sElementToMatch == $sElement) {
    			$bMatch = true;
    			continue;
    		} else {
    			return false;
    		}
    	}
    	return $bMatch;    	
    }

    /**
     * Method to create an instance of the default event object.
     * 
     * Parameters given are injected into the event object. Access the event object by $event->$variable = $value;
     * 
     * @param array $aParameters Associative array containing properties to be injected to the event
     * @return Event
     */
    public function makeEvent(array $aParameters = array())
    {
    	$event = new Event();

    	foreach($aParameters as $key => $value) {
    		$event->$key = $value;
    	}
    	
    	return $event;
    }
    
    /**
     * Triggers the listeners of an event.
     *
     * This method can be overridden to add functionality that is executed
     * for each listener.
     *
     * @param callable[] $aListenerData['callback'] The event listener callback.
     * @param string $eventIdentifier The name of the event to dispatch including full path.
     * @param Event|array $event The event object to pass to the event handlers/listeners, or an array with parameters used when creating a default event object
     * 
     * @return true|null Returns true if event was dispatched to a listener, null otherwise
     */
    public function dispatch($sEventIdentifier, $mEvent = null)
    {
    	$this->logger->debug('Sending dispatch signal/event: ' . $sEventIdentifier);
    	
    	if($mEvent === null) {
    		$event = $this->makeEvent();
    	} elseif (is_array($mEvent)) {
    		$event = $this->makeEvent($mEvent);
    	} else {
    		$event = $mEvent;
    	}
        $aEventData = $this->extractDataFromEventIdentifier($sEventIdentifier);
        
        $bEventHasListener = null;
        foreach($this->aListeners as $aListenerData) {
        	if($this->isMatch($aEventData, $aListenerData)) {
        		$this->logger->debug($sEventIdentifier . ' picked up by ' . get_class($aListenerData['callback']));
        		call_user_func($aListenerData['callback'], $event, $this);
        		$bEventHasListener = true;
        	}
        }
        
        return $bEventHasListener;
    }
}