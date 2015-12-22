<?php

namespace bblue\ruby\Entities;

/**
 * @Entity
 * 
 * @AssociationOverrides({
 *    @AssociationOverride(name="logins",
*           oneToMany=@OneToMany(targetEntity="bblue\ruby\Entities\LoginToken", mappedBy="user", fetch="EAGER")
 *    )
 * })
 */
class CurrentUser extends Member
{
    
}