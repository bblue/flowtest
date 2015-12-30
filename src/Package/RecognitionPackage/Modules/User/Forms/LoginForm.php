<?php

namespace bblue\ruby\Package\RecognitionPackage\Modules\User\Forms;

use bblue\ruby\Component\Form\Form;
use bblue\ruby\Package\RecognitionPackage\UserService;
use bblue\ruby\Component\Form\Element;
use bblue\ruby\Traits\Interpolate;

final class LoginForm extends Form
{   
    //@todo: Disse må endres slik at tekst og data flyttes sammen og oversettes ved behov inne i template, ikke her!
    const USERNAME_OR_PASSWORD_ERROR = 'Username or password incorrect'; 
    const LOGIN_ATTEMPTS_EXCEEDED = 'You have used up all your login attempts and need to wait 5 minutes before attempting again';
    const REMAINING_LOGIN_ATTEMPTS = 'You have {remainingLoginAttempts} attempts remaining';
    
    private $remainingLoginAttempts;
    private $maxAllowedLoginAttempts;
    
    public function __construct($sName, array $aData = array())
    {
        parent::__construct($sName, $aData);
        
        $this->createElement('username', 'email', true)->addValidationCallback(function(Element $element) {
            // Her kn mye magi skje
        });
        
        $this->createElement('password', 'password', true);
        
        if(empty($aData)) {
            return;
        }
        
        if(isset($aData['username'])) {
            $this->set('username', $aData['username']);
        }

        if(isset($aData['password'])) {
            $this->set('password', $aData['password']);
        }
        
        $sName = strtolower($sName);
        
        if(isset($aData[$sName])) {
            $this->set($sName, true);
        }
    }
    
    public function getUsername()
    {
        return $this->getElement('username')->getValue();
    }
    
    public function getPassword()
    {
        return $this->getElement('password')->getValue();
    }
    
    public function setRemainingLoginAttempts($remainingLoginAttempts)
    {
        $this->remainingLoginAttempts = $remainingLoginAttempts;
        if($remainingLoginAttempts <= 0) {
            $this->setError(self::LOGIN_ATTEMPTS_EXCEEDED);
            $this->disable();
        }
    }
    
    public function getRemainingLoginAttempts()
    {
        return $this->remainingLoginAttempts;
    }
    
    public function setMaxAllowedLoginAttempts($maxAllowedLoginAttempts)
    {
        $this->maxAllowedLoginAttempts = $maxAllowedLoginAttempts;
    }
    
    public function getMaxAllowedLoginAttempts()
    {
        return $this->maxAllowedLoginAttempts;
    }
    
}