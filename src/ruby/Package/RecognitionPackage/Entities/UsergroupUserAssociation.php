<?php

namespace bblue\ruby\Entities;

/**
 * @Entity
 * @Table(name="Usergroup_User_Association")
 */
class UsergroupUserAssociation
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;
    
    /**
     * @ManyToOne(targetEntity="bblue\ruby\Entities\User", inversedBy="assignedUsergroupUserAssociations")
     * @JoinColumn(name="user_id", referencedColumnName="id")
     * @var User
     */
    protected $user;
    
    /**
     * @ManyToOne(targetEntity="bblue\ruby\Entities\Usergroup", inversedBy="assignedUsergroupUserAssociations")
     * @JoinColumn(name="usergroup_id", referencedColumnName="id")
     * @var User
     */
    protected $usergroup;
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setUser(User $user)
    {
        $this->user = $user;
    }
    
    public function setUsergroup(Usergroup $usergroup)
    {
        $this->usergroup = $usergroup;
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function getUsergroup()
    {
        return $this->usergroup;
    }
}