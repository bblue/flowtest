<?php

namespace bblue\ruby\Entities;

use bblue\ruby\Component\Entity\Entity;

/**
 * @Entity @Table(name="visitors")
 **/
class Visitor extends Entity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;

    /**
     * @Column(type="datetime")
     * @var \DateTime
     */
    protected $lastSeen;
    
    public function setUser($user)
    {
        $this->user = $user;
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function hasUser()
    {
        return (isset($this->user) && $user instanceof User);
    }
    
    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }
    
    public function setLastSeen(\DateTime $lastSeen)
    {
        $this->lastSeen = $lastSeen;
    }
    
    public function getLastSeen()
    {
        return $this->lastSeen;
    }
    
    public function isLoggedIn()
    {
        if($user = $this->getUser()) {
            return (!$user->isGuest());
        }
    }
}