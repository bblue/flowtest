<?php

namespace bblue\ruby\Entities;

use bblue\ruby\Component\Entity\Entity;

/**
 * @Entity
 * @Table(name="Blocked_User_Association")
 */
class BlockedUserAssociation extends Entity
{    
    /**
     * @ManyToOne(targetEntity="bblue\ruby\Entities\User", inversedBy="assignedUserBlockedAssociations")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     * @var Member
     */
    protected $user;
    
    /**
     * @ManyToOne(targetEntity="bblue\ruby\Entities\User", inversedBy="assignedBlockedUserAssociations")
     * @JoinColumn(name="blocked_id", referencedColumnName="id")
     * @var Member
     */
    protected $blocked;
        
    public function setUser(User $user)
    {
        $this->user = $user;
    }
    
    public function setBlocked(User $blocked)
    {
        $this->blocked = $blocked;
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function getBlocked()
    {
        return $this->Blocked;
    }
}