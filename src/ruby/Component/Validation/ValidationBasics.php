<?php

namespace bblue\ruby\Component\Validation;

/**
 * Class with common basic validation methods
 * 
 * @author Aleksander Lanes
 *
 */
trait ValidationBasics
{
    /**
     * State of the validation. Defaults to false.
     * @var boolean
     */
    private $_validated = false;
    
    /**
     * Array of all errors, if any
     * @var array
     */
    private $_errors = array();
    
    /**
     * Will check if the target is valid or not. If the validation
     * has not run, it will trigger the $this->validate() method
     * 
     * @return boolean
     */
    public function isValid()
    {
        if(!$this->_validated) {
            $this->validate();
        }
        return !$this->hasError();
    }
    
    /**
     * Main validation method
     */
    public function validate()
    {
        throw new \Exception('Class using this trait ('.__CLASS__.') must implement the validate() method');
    }
    
    /**
     * Method to retrieve all errors, if any
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }
    
    /**
     * Checks if the validation instance has any errors
     * @return boolean
     */
    public function hasError()
    {
        return !(empty($this->getErrors()));
    }
}