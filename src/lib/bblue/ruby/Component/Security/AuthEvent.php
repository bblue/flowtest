<?php
namespace bblue\ruby\Component\Security;

use bblue\ruby\Component\EventDispatcher\EventInterface;

interface AuthEvent extends EventInterface
{
    const NO_AUTH_TOKEN = 'auth.no_auth_token';
}