<?php

namespace bblue\ruby\Component\Form;

class Form
{
    protected $aElements = array();
    private $sName;
    
    private $bSubmitted = false;
    private $bValidated = false;
    private $bDisabled = false;
    
    public $aFormErrors = array();
    
    protected $activeElement;
    
    public function __construct($sName, array $aData = array())
    {
        $this->sName = $sName;
        $this->bSubmitted = isset($aData[$sName]);
    }

    public function disable()
    {
        $elementNames = array_keys($this->aElements);
        foreach($elementNames as $sElementName) {
            $this->getElement($sElementName)->disable();
        }
        $this->bDisabled = true;
    }

    public function isDisabled()
    {
        return $this->bDisabled;
    }
    
    public function set($sElementName, $mValue)
    {
        if($this->hasElement($sElementName)) {
            $this->getElement($sElementName)->setValue($mValue);
            $this->bValidated = false;
        }
        return $this;
    }
    
    public function validate($sElementName = null)
    {
        return (isset($sElementName) ? $this->validateElement($sElementName) : $this->validateAllElements());       
    }
    
    public function validateElement($element)
    {
        if(is_string($element)) {
            if(!$element = $this->getElement($element)) {
                throw new \RuntimeException('Unknown element attempted validated');
            }
        }
        /* @var $element Element */
        if($element->isValid()) {
            $this->registerElementWithError($element);
            return false;
        }
        return true;
    }
    
    public function validateAllElements()
    {
        $bHasErrors = false;
        foreach($this->aElements as $sElementName => $aElementInfo) {
            $element = $aElementInfo['element'];
            if($element->isValid()) {
               continue; 
            }
            $this->registerElementWithError($element);
            $bHasErrors = true;
        }
        $this->bValidated = true;
        return $bHasErrors;
    }
    
    public function registerElementWithError(Element $element)
    {
        if($this->hasElement($element)) {
            $this->aElements[$element->getName()]['hasError'] = true;
            return $this;
        } else {
            throw new \RuntimeException('Unknown element');
        }
    }
    
    public function getName()
    {
        return isset($this->sName) ? $this->sName : 'form';
    }
    
    public function getErrors()
    {
        $aErrors = array();
        foreach($this->aElements as $sElementName => $aElementInfo) {
            if(!empty($aElementInfo['hasError'])) {
                $aElementErrors = $aElementInfo['element']->getErrors();
                foreach($aElementErrors as $error) {
                    $aErrors[$sElementName][] = $error;
                }
            }
        }
        
        if(!empty($aErrors)) {
            $aErrors = array_merge($aErrors, $this->aFormErrors);
        }
        
        return $aErrors;
    }
    
    public function getFormErrors()
    {
        return $this->aFormErrors;
    }
    
    public function hasErrors()
    {
        if(!empty($this->aFormErrors)) {
            return true;
        }
        
        foreach($this->aElements as $sElementName => $aElementInfo) {
            if(!empty($aElementInfo['hasError'])) {
                return true;
            }
        }
    }
    
    public function setError($sErrorMsg, $mElement = null)
    {
        if($mElement) {
            if($element = $this->getElement($mElement)) {
                $element->setError($sErrorMsg);
                $this->bValidated = false;
            }   
        } else {
            $this->aFormErrors[] = $sErrorMsg;
        }
    }
    
    public function isValid()
    {
        if(!$this->bValidated) {
            $this->validateAllElements();
        }
        return !$this->hasErrors();
    }

    public function isSubmitted($bSubmitted = null)
    {
        if(is_bool($bSubmitted)) {
           $this->bSubmitted = $bSubmitted; 
        }
        return $this->bSubmitted;
    }
    
    public function hasElement($mElement)
    {
        if($mElement instanceof Element) {
            $sElementName = $mElement->getName();
        } else {
            $sElementName = $mElement;
        }
        return array_key_exists($sElementName, $this->aElements);
    }

    /**
     * @param $sElementName
     * @return Element
     */
    public function getElement($sElementName)
    {
        if($this->hasElement($sElementName)) {
            $element = $this->aElements[$sElementName]['element'];
            return $element;
        }
    }
    
    public function addElement(Element $element)
    {
        $sElementName = $element->getName();
        if($this->hasElement($sElementName)) {
            throw new \RuntimeException('An element with this name already exists');
        }
        $this->aElements[$sElementName] = [
            'element'   => $element,
            'validated' => false
        ];
        return $this;
    }
    
    /**
     * Create a new form element
     * 
     * @param string $sName
     * @param string $type
     * @param boolean $required
     * @param int $min
     * @param int $max
     * @param boolean $trim
     * @param string $regex
     * @return \bblue\ruby\Component\Form\Element
     */
    public function createElement($sName, $type, $required=null, $min=null, $max=null, $trim=null, $regex=null)
    {
        $element = new Element($sName, $type, $required, $min, $max, $trim, $regex);
        
        $this->addElement($element);
        return $element;
    }
        
    private function hasActiveElement()
    {
        if($this->activeElement) {
            return true;
        } else {
            throw new \RuntimeException('An active element on the form has not been set');
        }
    }
    
    public function __get($variable)
    {
        return $this->getElement($variable);
    }
}