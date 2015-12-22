<?php

namespace bblue\ruby\Entities;

use bblue\ruby\Component\Security\PasswordHelper;

/**
 * @Entity
 */
class Member extends User
{ /**
     * Test if a provided password match the password in the entity
     *
     * @param string $sPassword
     * @return boolean True if the provided password matches entity, false otherwise
     */
    public function assertPasswordIsCorrect($password)
    {
        return PasswordHelper::matchPasswords($password, $this->getPasswordHash());
    }
    
    public function assertUsernameIsCorrect($sUsername)
    {
        return ($this->getUsername() === $sUsername);
    }

    public function assertUsernameIsValid($uname)
    {
        if($uname === 'guest') {
            return $this->_values['Username'] = $uname;
        }
        $this->validation
        ->addSource(array('username'=>$uname))
        ->addValidationRule('username', 'email', true)
        ->validate();
    
        if($aError = $this->validation->hasError()) {
            //throw new \Exception($aError['username']);
        } else {
            $this->_values['Username'] = $this->validation->sanitized['username'];
        }
        $this->validation->resetAll();
        return $this;
    }
    
    public function setPasswordHash($sPasswordHash)
    {
        $this->passwordHash = $sPasswordHash;
        return $this;
    }
    
    public function setPassword($sPassword)
    {
        $sPasswordHash = PasswordHelper::hashPassword($sPassword);
    
        $this->setPasswordHash($sPasswordHash);
    
        return $this;
    }
    
    public function generateNewPassword($length = null)
    {
        $sPassword = PasswordHelper::generatePassword($length);
        $sPasswordHash = PasswordHelper::hashPassword($sPassword);
    
        $this->setPasswordHash($sPasswordHash);
    
        return $this;
    }    
    
    /**
     * Returns the password hash of the entity
     */
    public function getPasswordHash()
    {
        if(!isset($this->passwordHash)) {
            throw new \RuntimeException('A password has not been set on this entity yet');
        }
    
        if(PasswordHelper::requiresRehash($this->passwordHash)) {
            $this->setPasswordHash(PasswordHelper::hashPassword($this->passwordHash));
        }
    
        return $this->passwordHash;
    }
    
    public function isGuest()
    {
        $userId = $this->getId();
        
        if ($userId == Guest::GUEST_ID) {
            throw new \UnexpectedValueException('Member class has id of guest. This is not allowed. Exiting for security purposes.');
        }
        
        return false;
    }
}