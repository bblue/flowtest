<?php

namespace bblue\ruby\Component\Security;

interface iAuthToken
{
    public function getUser();
    
    public function isValid($valid = null);
    public function getClientAddress();
    
    public function getUserAgent();
    
    public function getLoginHash();
    
    public function toArray();
}