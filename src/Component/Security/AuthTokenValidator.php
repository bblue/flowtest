<?php

namespace bblue\ruby\Component\Security;

use bblue\ruby\Component\Validation\iValidationBasics;
use bblue\ruby\Component\Validation\ValidationBasics;
use bblue\ruby\Entities\User;
use bblue\ruby\Component\Core\AbstractRequest;

final class AuthTokenValidator implements iAuthTokenChecker, iValidationBasics
{
    use ValidationBasics;
    
    private $token;
    private $request;

    const MAX_UNSUCCESSFUL_AUTH_ATTEMPTS = 5;
    
    public function __construct(iAuthToken $token, AbstractRequest $request)
    {
        $this->token = $token;
        $this->request = $request;
    }

    public function validate() //@todo: Disse skal trigge error, ikke exceptions
    {
        if($this->token->isValid() === false) {
            throw new AuthTokenException('Auth token is invalid');
        }
        
        if(!$user = $this->token->getUser()) {
            throw new AuthTokenException('Token does not contain a user object');
        }
        
        // Check if token login hash matches user login hash
        if($this->token->getLoginHash() !==  $user->getLoginHash()) {
            $this->token->isValid(false);
            throw new AuthTokenException('Invalid login hash');
        }
        
        // Check if token user agent matches current user agent
        if($this->token->getUserAgent() !== $this->request->getUserAgent()) {
            $this->token->isValid(false);
            throw new AuthTokenException('Client user agent does not match token user agent');
        }
        
        // Check if token IP matches current IP @todo: Det må være murlig å skru av denne sjekken, eventuelt tillatte noe endring 
        if($this->token->getClientAddress() !== $this->request->getClientAddress()) {
            $this->token->isValid(false);
            throw new AuthTokenException('Client address does not match token address');
        }
        
        // Check user has not gone past allowed auth attempts
        if($user->getAuthAttempts() > self::MAX_UNSUCCESSFUL_AUTH_ATTEMPTS) {
            $this->token->isValid(false);
            throw new AuthTokenException('Max auth attempts exceeded');
        }
        // Check user has not tried to authtenticate x amount of times the last y seconds
        
        // Check user can login via this token type
        
        // Log auth attempt

        $this->_validated = true; //@todo finne ut hvorfor jeg egentlig har denne. tror det er et spøkelse

        $this->token->isValid(true);

        return true;
    }
}

final class AuthTokenException extends \RuntimeException {};