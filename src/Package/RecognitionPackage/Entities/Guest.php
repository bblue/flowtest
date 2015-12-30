<?php
namespace bblue\ruby\Entities;

/**
 * @Entity(readOnly=true)
 * @HasLifecycleCallbacks
 * @todo Det er en rekke ting i User parent som ikke skal leses av denne klassen. Det må jeg fikse på et vis.
 */
final class Guest extends User
{
    const GUEST_USERNAME = 'Guest';
    const GUEST_ID = 0;
    
    public function __construct()
    {
        $this->username = self::GUEST_USERNAME;
        $this->id = self::GUEST_ID;
    }
    
    /**
     * @PreUpdate
     */
    public function assertUserIsNotGuest()
    {
        if($this->isGuest()) {
            throw new \Exception('Entity manager cannot update a guest entity');
        }
    }

    public function isGuest() {return true;}
}