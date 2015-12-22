<?php

namespace bblue\ruby\Component\Form;

use bblue\ruby\Component\Validation\Validation;

class Element
{
    const NAME_ATTRIBUTE = 'name';
    const VALUE_ATTRIBUTE = 'value';
    const DEFAULT_VALUE_ATTRIBUTE = 'default-value';
    const REQUIRED_ATTRIBUTE = 'required';
    const TYPE_ATTRIBUTE = 'type';
    const MIN_ATTRIBUTE = 'min';
    const MAX_ATTRIBUTE = 'max';
    const TRIM_ATTRIBUTE = 'trim';
    const REGEX_ATTRIBUTE = 'regex';
    const DISABLED_ATTRIBUTE = 'disabled';
    
    protected $aAttributes = array();
    protected $bValidated = false;
    
    protected $bErrorState = null;
    protected $aErrors = array();
    
    protected $aValidationCallbacks = array();
    protected $aValidationOverrides = array();
 
    public function __construct($sName, $type, $required=null, $min=null, $max=null, $trim=null, $regex=null, $disabled=null)
    {
        $this->setAttribute(self::NAME_ATTRIBUTE, $sName);
        $this->setAttribute(self::TYPE_ATTRIBUTE, $type);
        $this->setAttribute(self::REQUIRED_ATTRIBUTE, $required);
        $this->setAttribute(self::MIN_ATTRIBUTE, $min);
        $this->setAttribute(self::MAX_ATTRIBUTE, $max);
        $this->setAttribute(self::TRIM_ATTRIBUTE, $trim);
        $this->setAttribute(self::REGEX_ATTRIBUTE, $regex);
        $this->setAttribute(self::DISABLED_ATTRIBUTE, $disabled);
    }

    public function disable()
    {
        $this->setAttribute(self::DISABLED_ATTRIBUTE, true);
    }
    
    public function setAttribute($sAttribute, $mValue)
    {
        $this->aAttributes[$sAttribute] = $mValue;
        $this->bValidated = false;
        return $this;
    }

    public function getAttribute($sAttribute)
    {
        if(array_key_exists($sAttribute, $this->aAttributes)) {
            return $this->aAttributes[$sAttribute];
        }
    }
    
    public function getName()
    {
        return $this->getAttribute(self::NAME_ATTRIBUTE);
    }
    
    public function setValue($mValue)
    {
        $this->setAttribute(self::VALUE_ATTRIBUTE, $mValue);
    } 
      
    public function getValue()
    {
        return $this->getAttribute(self::VALUE_ATTRIBUTE);
    }
    
    public function addValidationCallback($callback)
    {
        $this->aValidationCallbacks[] = $callback;
        return $this;
    }
    
    public function addValidationOverride($callback)
    {
        $this->aValidationOverrides[] = $callback;
        return $this;
    }
    
    public function validate()
    {
        $sElementName = $this->getAttribute(self::NAME_ATTRIBUTE);
        $value = $this->getValue();
        
        if(!empty($this->validationOverrides)) {
            foreach($this->aValidationOverrides as $override) {
                call_user_func($override, $this);
            }
        } else {
            $validation = new Validation();
            $validation->addSource([$sElementName => $value]);
            $validation->addValidationRule($sElementName, $this->getAttribute(self::TYPE_ATTRIBUTE), $this->getAttribute(self::REQUIRED_ATTRIBUTE), $this->getAttribute(self::MIN_ATTRIBUTE), $this->getAttribute(self::MAX_ATTRIBUTE), $this->getAttribute(self::TRIM_ATTRIBUTE), $this->getAttribute(self::REGEX_ATTRIBUTE));
            $validation->validate();
            
            if($validation->hasError()) {
                $aErrors = $validation->getErrors();
                foreach($aErrors as $error) {
                    $this->setError($error);
                }
            } else {
                $value = $validation->sanitized[$sElementName];
                $this->setValue($value);
                
                if(!empty($this->aValidationCallbacks)) {
                    foreach($this->aValidationCallbacks as $callback)  {
                        call_user_func($callback, $this);
                    }
                }
            }
        }
        
        $this->bValidated = true;
    }
    
    /**
     * @todo tillate at det lagres flere errors
     * @param string $sErrorMsg
     */
    public function setError($sErrorMsg = '') //@todo: Lage en egen sak for å tracke errors i klasser
    {
        $this->aErrors[] = $sErrorMsg;
        $this->bErrorState = true;
        return $this;
    }
    
    public function isValidated()
    {
        return $this->bValidated;
    }
      
    public function getErrors()
    {
        return $this->aErrors;
    }
        
    public function hasError()
    {
        if(is_null($this->bErrorState)) {
            $this->bErrorState = empty($this->aErrors) ? false : true;
        }
        return $this->bErrorState;
    }
    
    public function isValid()
    {
        if($this->bValidated !== true) {
            $this->validate();
        }
        return (!$this->hasError());
    }
}