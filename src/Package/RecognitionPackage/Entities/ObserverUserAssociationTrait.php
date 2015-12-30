<?php

namespace bblue\ruby\Package\RecognitionPackage\Entities;

use bblue\ruby\Entities\ObserverUserAssociation;

trait ObserverUserAssociationTrait
{
    /**
     * Adds an already assigned Usergroup-User association
     * 
     * @param ObserverUserAssociation $association
     * @return Entity Returns the entity extending the trait  
     */
    public function assignedObserverUserAssociations(ObserverUserAssociation $association)
    {
        $this->assignedObserverUserAssociations[] = $association;
        return $this;
    }

    /**
     * Returns all assigned Usergroup - User associations
     * 
     * @return ObserverUserAssociation[]
     */
    public function getObserverUserAssociations()
    {
        return $this->assignedObserverUserAssociations;
    }
}