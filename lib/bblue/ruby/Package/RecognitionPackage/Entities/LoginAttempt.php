<?php

namespace bblue\ruby\Entities;

use bblue\ruby\Component\Entity\Entity;

/**
 * @Entity
 */
class LoginAttempt extends Entity
{
    const SUCCESSFUL_LOGIN = 1;
    const FAILED_LOGIN = 0;
    
    private $status;
    
    private $ip;
    
    /**
     * @var User
     */
    protected $user;
    
    public function setStatus($status)
    {
        $this->status = $status;
    }
    
    public function setIP($ip)
    {
        $this->ip = $ip;
    }
}
