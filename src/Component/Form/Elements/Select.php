<?php

namespace bblue\ruby\Component\Form\Elements;

use bblue\ruby\Component\Form\Element;

class Select extends Element
{   
    use OptionAwareTrait;
    
    protected $aOptionGroups = array();
    
    public function __construct($sName)
    {
        $this->setAttribute('name', $sName);
    }
    
    public function addOptionGroup(OptionGroup $optGroup)
    {
        $this->aOptionGroups[] = $optGroup;
        return $this;
    }
    
    public function createOptionGroup($sLabel = null)
    {
        $optGroup = new OptionGroup($sLabel);
        $this->addOptionGroup($optGroup);
        return $optGroup;
    }
}