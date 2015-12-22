<?php

namespace bblue\ruby\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use bblue\ruby\Component\Entity\Entity;

/**
 * @Entity
 * @Table(name="usergroups")
 **/
abstract class Usergroup extends Entity
{
    /**
     * @Column(type="string")
     * @var string
     */
    protected $name;
        
    /**
     * @OneToMany(targetEntity="bblue\ruby\Entities\UsergroupUserAssociation", mappedBy="usergroup")
     * @var UsergroupUserAssociation[]
     */
    protected $assignedUsergroupUserAssociations = null;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->assignedUsergroupUserAssociations = new ArrayCollection();
    }
}