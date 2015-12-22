<?php
namespace bblue\ruby\Component\Security;

use bblue\ruby\Entities\User;
use bblue\ruby\Component\Validation\iValidationBasics;

final class UserChecker implements iUserChecker, iValidationBasics
{
    use bblue\ruby\Component\Validation\ValidationBasics;
    
    private $_user;
    
    public function __construct(User $user = null)
    {
        if(isset($user)) {
            $this->setUser($user);
        }
    }
    
    public function setUser(User $user)
    {
        if(isset($this->_user)) {
            throw new \Exception('Failed to set user. A user is already set.');
        }
        $this->_user = $user;
        return $this;
    }
    
    public function validate()
    {
        // Check if user is active
        // Check if user is blocked
        // Check if user is locked
        // Check if user is active (vs. inactive)
        
        $this->_validated = true;
    }
}