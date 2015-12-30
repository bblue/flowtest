<?php
namespace bblue\ruby\Component\Flasher;

interface FlasherInterface
{
    /**
     * Notify user of successful event
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function success($message, array $context = array());
    
    /**
     * Provide user with information about an event
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function info($message, array $context = array());
    
    /**
     * Give user a warning about an event
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function warning($message, array $context = array());
    
    /**
     * Notify user of error
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public function error($message, array $context = array());
    
    /**
     * Flashes with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function flash($level, $message, array $context = array());
    
    /**
     * Read-only method to view flashes for a given level and (optional) index
     * 
     * @param string $level
     * @param integer $index Optional index
     * @return array The flash messages
     */
    public function peek($level, $index = null);
    
    /**
     * Read-only method to view all flashes
     *
     * @return array The flash messages
     */
    public function peekAll();
    
    /**
     * Retrieve and flush flashes for a given level and (optional) index
     *
     * @param string $level
     * @param integer $index Optional index
     * @return array The flash messages
     */
    public function get($level, $index = null);
    
    /**
     * Retrieve and flush all flashes
     *
     * @return array The flash messages
     */    
    public function getAll();
    
    /**
     * Flush a specific level and (optional) index
     *
     * @param string $level
     * @param integer $index Optional index
     * @return void;
     */
    public function flush($level, $index = null);
    
    /**
     * Flush all flashes
     * 
     * @return void;
     */
    public function flushAll();
}