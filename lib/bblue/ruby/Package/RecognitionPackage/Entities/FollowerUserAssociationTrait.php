<?php

namespace bblue\ruby\Package\RecognitionPackage\Entities;

use bblue\ruby\Entities\FollowerUserAssociation;

trait FollowerUserAssociationTrait
{
    /**
     * Adds an already assigned Usergroup-User association
     * 
     * @param FollowerUserAssociation $association
     * @return Entity Returns the entity extending the trait  
     */
    public function assignedFollowerUserAssociations(FollowerUserAssociation $association)
    {
        $this->assignedFollowerUserAssociations[] = $association;
        return $this;
    }

    /**
     * Returns all assigned Usergroup - User associations
     * 
     * @return FollowerUserAssociation[]
     */
    public function getFollowerUserAssociations()
    {
        return $this->assignedFollowerUserAssociations;
    }
}