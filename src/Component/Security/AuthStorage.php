<?php
namespace bblue\ruby\Component\Security;

use bblue\ruby\Component\Core\SessionHandler;
use bblue\ruby\Component\Core\iUserProvider;

final class AuthStorage implements iAuthStorage
{
    /**
     * @var SessionHandler
     */
    private $session;

    private $tokenFactory;
    
    private $userProvider;
    
    const AUTH_TOKEN_VAR_NAME = 'authTokenID';
    
    public function __construct(SessionHandler $session, AuthTokenFactory $tokenFactory, iUserProvider $userProvider) 
    {
        $this->session = $session;
        $this->tokenFactory = $tokenFactory;
        $this->userProvider = $userProvider;
    }
    
    public function getToken() 
    {
        if($data = $this->session->query(self::AUTH_TOKEN_VAR_NAME)) {
            $token = $this->tokenFactory->build($data);
            $token->setUser($this->userProvider->getById($token->getUserId()));
            return $token;
        }
    }
    
    public function storeToken(iAuthToken $token)
    {
        if(!$token->isValid()) {
            throw new \Exception('Auth token is invalid. Refusing to store it');
        }
        $this->session->set(self::AUTH_TOKEN_VAR_NAME, $token->toArray());
        return true;
    }
    
    public function deleteToken()
    {
        $this->session->delete(self::AUTH_TOKEN_VAR_NAME);
    }
}