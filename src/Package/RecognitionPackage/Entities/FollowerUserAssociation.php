<?php

namespace bblue\ruby\Entities;

use bblue\ruby\Component\Entity\Entity;

/**
 * @Entity
 * @Table(name="Follower_User_Association")
 */
class FollowerUserAssociation extends Entity
{    
    /**
     * @ManyToOne(targetEntity="bblue\ruby\Entities\User", inversedBy="assignedUserFollowerAssociations")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     * @var Member
     */
    protected $user;
    
    /**
     * @ManyToOne(targetEntity="bblue\ruby\Entities\User", inversedBy="assignedFollowerUserAssociations")
     * @JoinColumn(name="follower_id", referencedColumnName="id")
     * @var Member
     */
    protected $follower;
        
    public function setUser(User $user)
    {
        $this->user = $user;
    }
    
    public function setFollower(User $follower)
    {
        $this->follower = $follower;
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function getFollower()
    {
        return $this->follower;
    }
}