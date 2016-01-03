<?php

namespace bblue\ruby\Component\EventDispatcher;

class Event implements EventInterface
{
    protected $data = array();
    private $caller;

    public function __construct(array $parameters = array())
    {
        foreach ($parameters as $key => $value) {
            $this->$key = $value;
        }
    }
    
    public function __set($parameter, $value)
    {
        $this->data[$parameter] = $value;
        return $this;
    }

    public function __get($parameter)
    {
        if(!isset($this->data[$parameter])) {
            throw new \UnexpectedValueException($parameter . ' is unset');
        }
        return $this->data[$parameter];
    }
}
