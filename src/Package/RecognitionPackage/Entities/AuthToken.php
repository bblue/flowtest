<?php

namespace bblue\ruby\Entities;

use bblue\ruby\Component\Entity\Entity;

/**
 * The login token represents a successful login (and thereby also authentication)
 * @Entity
 * @HasLifecycleCallbacks
 */
class AuthToken extends Entity
{
    /**
     * @Column(type="string")
     * @var string
     */
    protected $originalIP;
    
    protected $_hasPassedAuthCheck = false;
   
    protected $valid;
    
    public function setUser(User $user)
    {
        $this->user = $user;
    } 

    /**
     * The user object related to this login token
     * @ManyToOne(targetEntity="bblue\ruby\Entities\User", inversedBy="auths")
     * @var User
     **/
    private $user;
    
    /** @PreUpdate */
    private function _assertTokenHasPassedAuth()
    {
        // Only check auth if this is a first insert operation
        if(!$this->id) {
            if(!$this->cleared()) {
                throw new \Exception('Login token has not been cleared by any auth service. Unable to insert login token in database');
            }
        }
    }
    
    public function cleared($status = null)
    {
        if(isset($status)) {
            $this->_hasPassedAuthCheck = $status;
            return $this;
        } else {
            return $this->_hasPassedAuthCheck;
        }
    }
    
    public function getOriginalIP()
    {
        return null;
    }
    
    public function getUser()
    {
        if($this->_authenticateStatus === self::STATUS_AUTHENTICATION_FAILED) {
            throw new \Exception('Unable to retrieve user object when auth status is false');
        } else {
            return $this->user;            
        }
    }
    
    public function hasUser()
    {
        return isset($this->user);
    }
    
    public function isValid($valid = null)
    {
        if(isset($valid)) {
            $this->valid = (bool)$valid;
            return $this;
        } else {
            return $this->valid;
        }
    }
}