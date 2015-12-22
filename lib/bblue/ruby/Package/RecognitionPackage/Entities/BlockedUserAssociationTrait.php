<?php

namespace bblue\ruby\Package\RecognitionPackage\Entities;

use bblue\ruby\Entities\BlockedUserAssociation;

trait BlockedUserAssociationTrait
{
    /**
     * Adds an already assigned Usergroup-User association
     * 
     * @param BlockedUserAssociation $association
     * @return Entity Returns the entity extending the trait  
     */
    public function assignedBlockedUserAssociations(BlockedUserAssociation $association)
    {
        $this->assignedBlockedUserAssociations[] = $association;
        return $this;
    }

    /**
     * Returns all assigned Usergroup - User associations
     * 
     * @return BlockedUserAssociation[]
     */
    public function getBlockedUserAssociations()
    {
        return $this->assignedBlockedUserAssociations;
    }
}