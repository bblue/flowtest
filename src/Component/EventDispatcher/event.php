<?php

namespace bblue\ruby\Component\EventDispatcher;

class Event implements EventInterface
{
    protected $_data = array();
    private $dispatcher;
    private $caller;

    public function __construct(array $params = array())
    {
        foreach ($params as $key => $value) {
            $this->$key = $value;
        }
    }
    
    public function __set($sParam, $mValue)
    {
        $this->_data[$sParam] = $mValue;
        return $this;
    }

    public function __get($sParam)
    {
        if(isset($this->_data[$sParam])) {
            return $this->_data[$sParam];
        } else {
            throw new \UnexpectedValueException($sParam . ' is unset');
        }
    }
}
