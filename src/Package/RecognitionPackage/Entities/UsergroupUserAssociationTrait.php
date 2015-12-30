<?php

namespace bblue\ruby\Package\RecognitionPackage\Entities;

use bblue\ruby\Entities\UsergroupUserAssociation;

trait UsergroupUserAssociationTrait
{
    /**
     * Adds an already assigned Usergroup-User association
     * 
     * @param UsergroupUserAssociation $association
     * @return Entity Returns the entity extending the trait  
     */
    public function assignedToUsergroupUserAssociation(UsergroupUserAssociation $association)
    {
        $this->assignedUsergroupUserAssociations[] = $association;
        return $this;
    }

    /**
     * Returns all assigned Usergroup - User associations
     * 
     * @return UsergroupUserAssociation[]
     */
    public function getUsergroupUserAssociations()
    {
        return $this->assignedUsergroupUserAssociations;
    }
}