<?php

namespace bblue\ruby\Component\Form\Elements;

use bblue\ruby\Component\Form\Element;

class OptionGroup extends Element
{
    use OptionAwareTrait;
    
    public function __construct($sLabel = null)
    {
        $this->setAttribute('label', $sLabel);
    }
}