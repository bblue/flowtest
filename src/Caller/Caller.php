<?php

namespace bblue\ruby\Caller;

final class Caller
{
    public $file;
    public $line;
    public $type;
    public $class;
    public $function;
    
    public function __construct($sublevel = 1)
    {
        $this->_traceCaller($sublevel);
        if(PHP_VERSION >='7.0.0') {
            trigger_error('This version of list is not compatible with PHP7');
        }
        return $this;
    }
    
    private function _traceCaller($sublevel)
    {
        if($sublevel == 0) {
            list(,,$caller) = debug_backtrace(false);
        } elseif($sublevel == 1) {
            list(,,,$caller) = debug_backtrace(false);
        } elseif($sublevel == 2) {
            list(,,,,$caller) = debug_backtrace(false);
        } else {
            throw new \OutOfRangeException('This sublevel is not supported');
        }

        unset($caller['args']);
        foreach($caller as $key => $value) {
            $this->$key = $value;
        }
    }
    
    public function getMethodAsString()
    {
        return "$this->class$this->type$this->function()";
    }
    
    public function getFileAsString()
    {
        return "$this->file on line number $this->line";
    }
}