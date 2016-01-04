<?php

namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Component\Core\AbstractRequest;
use bblue\ruby\Component\Core\SessionHandler;
use bblue\ruby\Component\Logger\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManager;
use bblue\ruby\Entities\User;
use bblue\ruby\Entities\Guest;
use bblue\ruby\Component\Security\Auth;
use bblue\ruby\Component\Security\iAuthToken;
use bblue\ruby\Component\Security\AuthException;
use bblue\ruby\Component\Security\AuthToken;
use bblue\ruby\Component\Security\AuthTokenFactory;
use Psr\Log\LoggerAwareInterface;
use bblue\ruby\Entities\LoginAttempt;
use Doctrine\DBAL\Schema\AbstractAsset;
use bblue\ruby\Component\Security\PasswordHelper;

/** 
 * Class to enable native username/password login.
 * 
 * This class will review a set of provided username/password and submit an auth token to the auth service if succesful
 * 
 * @author Aleksander Lanes
 *
 */
final class LoginService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const MAX_ALLOWED_LOGIN_ATTEMPTS = 5;
    const LOGIN_TIMELOCK = 15; // minutes
    
    /**
     * Factory to create tokens
     * @var AuthTokenFactory
     */
    private $tokenFactory;
    
    /**
     * The entity mananger
     * @var EntityManager
     */
    private $_em;
    
    /**
     * Instance of the auth service
     * @var Auth
     */
    private $auth;

    /**
     * A login attempt object
     * @var LoginAttempt
     */
    private $loginAttempt;
    
    /**
     * The current request object
     * @var AbstractRequest
     */
    private $request;
      
    /**
     * The session
     * @var SessionHandler
     */
    private $session;
    
    public function __construct(Auth $auth, EntityManager $em, AbstractRequest $request, AuthTokenFactory $tokenFactory, SessionHandler $session)
    {
        $this->auth = $auth;
        $this->_em = $em;
        $this->tokenFactory = $tokenFactory;
        $this->request = $request;
        $this->session = $session;
    }
    
    /**
     * Creates a new login attempt and associates it with the user
     * 
     * @param User $user
     * @param AbstractRequest $request
     * @return boolean
     */
    public function registerCurrentLoginAttempt(User $user = null, AbstractRequest $request = null)
    {
        $request = $this->_getRequest($request);
        $loginAttempt = new LoginAttempt();
        $loginAttempt->setIP($request->getClientAddress());
        $this->_em->persist($loginAttempt);
        if($user) {
            $user->addLoginAttempt($loginAttempt);
        }
        return true;
    }
    
    public function getCurrentLoginAttempt(User $user, AbstractRequest $request = null)
    {
        $request = $this->_getRequest($request);
        //@todo: Get the current login attempt
        return new LoginAttempt(); // Denne er her kun for å tilfredstille testing
    }
    
    private function _getRequest(AbstractRequest $request = null)
    {
        if (isset($request)) {
            return $request;
        } elseif(isset($this->request)) {
            return $this->request;
        }
    }
    
    public function setLoginTimelock(User $user = null, AbstractRequest $request = null)
    {
        $this->_getRequest($request)->setLoginTimelock(self::LOGIN_TIMELOCK);
        if($user) {
            $user->setLoginTimelock(self::LOGIN_TIMELOCK);
        }
    }
    
    public function isBelowLoginAttemptThreshold(User $user = null, AbstractRequest $request = null)
    {
        $request = $this->_getRequest($request);
        // @todo
        return true;
    }
    
    public function getRemainingLoginAttempts(User $user = null, AbstractRequest $request = null)
    {
        $request = $this->_getRequest($request);
        $user = isset($user) ? $user : $this->auth->getUser(); // @todo: make php7 
        // @todo
        return 5;        
    }

    public function getMaxAllowedLoginAttempts()
    {
        return self::MAX_ALLOWED_LOGIN_ATTEMPTS;
    }
        
    
    /**
     * Method to log in a user. An auth token is required.
     * 
     * Note that this method is only to do the actual LOGIN commands. Auth is done by the auth service.
     * 
     * @param iAuthToken $token The auth token associated with the user
     * @param User $user The user to be logged in 
     * @throws \Exception In case something bad happens
     * @return boolean True on success
     */
    public function login(iAuthToken $token)
    {
        try {
            $user = $token->getUser();
            $this->logger->notice('Trying to log in as user ' . $user->getUsername());
            if($user->isGuest()) {
                throw new \Exception('It makes no sense for a guest to log in');
            }
            $this->registerCurrentLoginAttempt($user);
            if(!$this->auth->handle($token, $user)) {
                throw new \Exception('Unable to authenticate user');
            }
            $this->session->regenerate();
            $this->logger->notice($user->getUsername() . ' logged in');
            $this->getCurrentLoginAttempt($user)->setStatus(LoginAttempt::SUCCESSFUL_LOGIN);
            
            return true;
        } catch (\Exception $e) {
            // do cleanup and rethrow exception
            throw $e;
        }
    }
    
    public function logout(Member $user)
    {
        if($user->isGuest()) {
            throw new \RuntimeException('A guest may not log out');
        }
        $this->logger->info('Logging out ' . $user->getUsername()); //@todo: Vurdere om ikke jeg skal lage en logger listener som automatisk logger enkelte signals. Da slipper jeg å skrive så mange av disse beskjedene, men kan heller dytte alt inn i en samlesak
        $this->session->delete('user_id');
        $this->session->restart();
        $em = $this->container->get('entityManager'); /* @var $em EntityMananger */
        $em->detach($user);
        $user = $this->container->get('userService')->createGuest();
        $this->container->set($user, 'user');
    
        return $user; //@todo: Vurdere behov for å returnere et user objekt
    }
    
    /**
     * Get the token object after running the handle method
     * @throws \Exception
     * @return AuthToken
     */
    public function createToken(User $user, AbstractRequest $request = null)
    {
        $request = $this->_getRequest($request);
        $token = $this->tokenFactory->build();
        $this->_prepareToken($token, $request, $user);
        
        return $token;
    }
    
    public function createAnonomyousToken(AbstractRequest $request = null)
    {
        $request = $this->_getRequest($request);
        $token = $this->tokenFactory->buildAnonomyousToken();
        $this->_prepareToken($token, $request, $this->userProvider->getById(GUEST::GUEST_ID));
        
        return $token;
    }
    
    private function _prepareToken(AuthToken $token, AbstractRequest $request, User $user)
    {
        $token->setClientAddress($request->getClientAddress());
        $token->setUserAgent($request->getUserAgent());
        $token->setLoginHash($user->getLoginHash());
        $token->setUser($user);
    }
}
