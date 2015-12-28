<?php
namespace bblue\ruby\Component\Security;

interface iAuthStorage
{
    public function storeToken(iAuthToken $token);
    
    public function getToken();
}