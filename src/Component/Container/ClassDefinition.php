<?php

namespace bblue\ruby\Component\Container;

final class ClassDefinition
{
    private $aConstructorArguments = array();
    private $aMethodCalls = array();
    private $aParameters = array();
    
    public $sIncludePath = '';

    private $sFullClassName;
    private $definedBy;
    
    public function definedBy($definer = null)
    {
        if($definer) {
            $this->definedBy = $definer;
        } else {
            return $this->definedBy;
        }
    }
    
    public function __construct($sFullClassName)
    {
        $this->sFullClassName = $sFullClassName;
    }
    
    public function getFullClassName()
    {
        return $this->sFullClassName;
    }
    
    public function setParameter($sParameterName, $value)
    {
        $this->aParameters[$sParameterName] = $value;
    }
    
    public function getParameters()
    {
        return $this->aParameters;
    }
    
    /**
     * @todo Rename this to constructor Params
     * @param unknown $argument
     */
    public function addConstructorArgument($argument, $index = null)
    {
        if($index) {
            $this->aConstructorArguments[$index] = $argument; 
        } else {
            $this->aConstructorArguments[] = $argument;
        }
    }
    
    public function getConstructorArguments()
    {
        return $this->aConstructorArguments;
    }
    
    public function getMethodCalls()
    {
        if(!$this->hasMethodCalls()) {
            throw new  \Exception("Definition object does not have any method calls", 1);
        }
        return $this->aMethodCalls;
    }
    
    /**
     * Check if the definition object has any method calls lined up
     * @return boolean Returns true if one or more method calls are present
     */
    public function hasMethodCalls()
    {
        return !empty($this->aMethodCalls);
    }

    public function addMethodCall($sMethod, array $aParameters)
    {
        $this->aMethodCalls[] = array(
            'sMethod'       => $sMethod,
            'aParameters'   => $aParameters
        );
        return $this;
    }
}