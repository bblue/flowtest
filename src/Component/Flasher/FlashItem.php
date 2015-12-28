<?php
namespace bblue\ruby\Component\Flasher;

use bblue\ruby\Component\Flasher\FlashLevel as FlashLevel;

final class FlashItem
{
    private $level;
    private $msg;
    private $context;
    private $index;
    
    public function __construct($level, $msg, array $context = array())
    {      
        $this->level = $level;
        $this->msg = $msg;
        $this->context = $context;
    }
    
    public function getLevel()
    {
        return $this->level;
    }
    
    public function getMessage()
    {
        return $this->msg;
    }
    
    public function getContext()
    {
        return $this->context;
    }
    
    public function setIndex($index)
    {
        $this->index = $index;
    }
    
    public function getIndex()
    {
        return $this->index;
    }
   
}