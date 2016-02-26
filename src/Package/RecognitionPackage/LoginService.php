<?php

namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Component\Core\SessionHandler;
use bblue\ruby\Component\Logger\tLoggerAware;
use bblue\ruby\Component\Request\iInternalRequest;
use bblue\ruby\Component\Security\Auth;
use bblue\ruby\Component\Security\AuthToken;
use bblue\ruby\Component\Security\AuthTokenFactory;
use bblue\ruby\Component\Security\iAuthToken;
use bblue\ruby\Entities\Guest;
use bblue\ruby\Entities\LoginAttempt;
use bblue\ruby\Entities\User;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerAwareInterface;

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
    use tLoggerAware;

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
     * @var iInternalRequest
     */
    private $request;
      
    /**
     * The session
     * @var SessionHandler
     */
    private $session;
    
    public function __construct(Auth $auth, EntityManager $em, iInternalRequest $request, AuthTokenFactory $tokenFactory,
                                SessionHandler $session)
    {
        $this->auth = $auth;
        $this->_em = $em;
        $this->tokenFactory = $tokenFactory;
        $this->request = $request;
        $this->session = $session;
    }

    public function createAnonomyousToken(iInternalRequest $request = null)
    {
        $request = $this->_getRequest($request);
        $token = $this->tokenFactory->buildAnonomyousToken();
        $this->_prepareToken($token, $request, $this->userProvider->getById(GUEST::GUEST_ID));

        return $token;
    }

    private function _getRequest(iInternalRequest $request = null)
    {
        if (isset($request)) {
            return $request;
        } elseif (isset($this->request)) {
            return $this->request;
        }
    }

    private function _prepareToken(AuthToken $token, iInternalRequest $request, User $user)
    {
        $token->setClientAddress($request->getClientAddress());
        $token->setUserAgent($request->getUserAgent());
        $token->setLoginHash($user->getLoginHash());
        $token->setUser($user);
    }

    /**
     * Get the token object after running the handle method
     * @throws \Exception
     * @return AuthToken
     */
    public function createToken(User $user, iInternalRequest $request = null)
    {
        $request = $this->_getRequest($request);
        $token = $this->tokenFactory->build();
        $this->_prepareToken($token, $request, $user);

        return $token;
    }

    public function getMaxAllowedLoginAttempts()
    {
        return self::MAX_ALLOWED_LOGIN_ATTEMPTS;
    }

    public function getRemainingLoginAttempts(User $user = null, iInternalRequest $request = null)
    {
        $request = $this->_getRequest($request);
        $user = isset($user) ? $user : $this->auth->getUser(); // @todo: make php7
        // @todo
        return 5;
    }

    public function isBelowLoginAttemptThreshold(User $user = null, iInternalRequest $request = null)
    {
        $request = $this->_getRequest($request);
        // @todo
        return true;
    }

    /**
     * Method to log in a user. An auth token is required.
     * Note that this method is only to do the actual LOGIN commands. Auth is done by the auth service.
     * @param iAuthToken $token The auth token associated with the user
     * @return bool In case something bad happens
     * @throws \Exception In case something bad happens
     * @internal param User $user The user to be logged in
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

    /**
     * Creates a new login attempt and associates it with the user
     * @param User            $user
     * @param iInternalRequest $request
     * @return boolean
     */
    public function registerCurrentLoginAttempt(User $user = null, iInternalRequest $request = null)
    {
        $request = $this->_getRequest($request);
        $loginAttempt = new LoginAttempt();
        $loginAttempt->setIP($request->getClientAddress());
        $this->_em->persist($loginAttempt);
        if ($user) {
            $user->addLoginAttempt($loginAttempt);
        }
        return true;
    }

    public function getCurrentLoginAttempt(User $user, iInternalRequest $request = null)
    {
        $request = $this->_getRequest($request);
        //@todo: Get the current login attempt
        return new LoginAttempt(); // Denne er her kun for � tilfredstille testing
    }
    
    public function logout(Member $user)
    {
        if($user->isGuest()) {
            throw new \RuntimeException('A guest may not log out');
        }
        $this->logger->info('Logging out ' . $user->getUsername()); //@todo: Vurdere om ikke jeg skal lage en logger listener som automatisk logger enkelte signals. Da slipper jeg � skrive s� mange av disse beskjedene, men kan heller dytte alt inn i en samlesak
        $this->session->delete('user_id');
        $this->session->restart();
        $em = $this->container->get('entityManager'); /* @var $em EntityMananger */
        $em->detach($user);
        $user = $this->container->get('userService')->createGuest();
        $this->container->set($user, 'user');

        return $user; //@todo: Vurdere behov for � returnere et user objekt
    }

    public function setLoginTimelock(User $user = null, iInternalRequest $request = null)
    {
        $this->_getRequest($request)->setLoginTimelock(self::LOGIN_TIMELOCK);
        if ($user) {
            $user->setLoginTimelock(self::LOGIN_TIMELOCK);
        }
    }
}
