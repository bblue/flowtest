<?php
namespace bblue\ruby\Component\Security;

use bblue\ruby\Package\RecognitionPackage\AnonomyousAuthToken;

final class AuthTokenFactory
{
    public function build($data = null)
    {
        return new AuthToken($data);
    }
    
    public function buildAnonomyousToken()
    {
        return new AnonomyousAuthToken();
    }
}