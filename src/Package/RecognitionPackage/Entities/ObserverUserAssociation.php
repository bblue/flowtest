<?php

namespace bblue\ruby\Entities;

use bblue\ruby\Component\Entity\Entity;

/**
 * @Entity
 * @Table(name="Observer_User_Association")
 */
class ObserverUserAssociation extends Entity
{    
    /**
     * @ManyToOne(targetEntity="bblue\ruby\Entities\User", inversedBy="assignedUserObserverAssociations")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     * @var User
     */
    protected $user;
    
    /**
     * @ManyToOne(targetEntity="bblue\ruby\Entities\User", inversedBy="assignedObserverUserAssociations")
     * @JoinColumn(name="observer_id", referencedColumnName="id")
     * @var User
     */
    protected $observer;
        
    public function setUser(User $user)
    {
        $this->user = $user;
    }
    
    public function setObserver(User $observer)
    {
        $this->observer = $observer;
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function getObserver()
    {
        return $this->observer;
    }
}