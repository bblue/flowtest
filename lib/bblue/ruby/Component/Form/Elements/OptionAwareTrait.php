<?php

namespace bblue\ruby\Component\Form\Elements;

trait OptionAwareTrait
{
    protected $aOptions = array();
    
    public function addOption(Option $option)
    {
        $this->aOptions[] = $option;
    }
    
    public function getOptions()
    {
        return $this->aOptions;
    }
}