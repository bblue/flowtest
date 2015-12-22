<?php

namespace bblue\ruby\Component\Flasher;

use bblue\ruby\Traits\ArrayFunctions;
abstract class FlasherStorageAbstract
{
    use ArrayFunctions;
    
    /**
     * Internal in-memory cache
     * @var unknown
     */
    protected $_cache = array();
    
    protected function inCache($level, $index)
    {
        if(array_key_exists($level, $this->_cache)) {
            return array_key_exists($index, $this->_cache[$level]);
        }
        
    }
    
    protected function getFromCache($level = null, $index = null)
    {
        if(!$level) {
            return $this->array_flatten($this->_cache);
        } elseif($index) {
            return array($this->_cache[$level][$index]);
        } else {
            return $this->array_flatten($this->_cache[$level]);
        }
    }

    protected function storeInCache(FlashItem $flash)
    {
        $this->_cache[$flash->getLevel()][$flash->getIndex()] = $flash;        
    }
    
    protected function buildFlashItem($level, $message, array $context = array())
    {
        return new FlashItem($level, $message, $context);
    }
}