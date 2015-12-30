<?php
namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Component\Security\AuthToken;
use bblue\ruby\Entities\User;

final class AnonomyousAuthToken extends AuthToken
{
    public function getUserId()
    {
        return User::GUEST_ID;
    }
}