<?php

namespace bblue\ruby\Component\Container;

final class Reference
{
    private $sName; 
    
    public function __construct($sName)
    {
        $this->sName = $sName;
    }
    
    public function getName()
    {
        return $this->sName;
    }
}