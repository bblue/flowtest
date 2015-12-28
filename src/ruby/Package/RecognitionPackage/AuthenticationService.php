<?php

namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Container\ContainerAwareTrait;
use bblue\ruby\Entities\User;
use bblue\ruby\Component\Core\AbstractRequest as Request;
use bblue\ruby\Component\EventDispatcher\Event;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerAwareInterface;
use bblue\ruby\Component\Logger\LoggerAwareTrait;
use bblue\ruby\Package\RecognitionPackage\Modules\User\Forms\LoginForm;
use bblue\ruby\Component\Security\PasswordHelper;
use bblue\ruby\Component\Core\SessionHandler;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Entities\LoginToken;
use bblue\ruby\Package\RecognitionPackage\Entities\Guest;
use bblue\ruby\Package\DatabasePackage\Doctrine;
use bblue\ruby\Package\DatabasePackage\DoctrineEvent;
use bblue\ruby\Entities\Member;
use bblue\ruby\Entities\AuthToken;
use bblue\ruby\Entities\LoginAttempt;

final class AuthenticationService implements ContainerAwareInterface, LoggerAwareInterface, EventDispatcherAwareInterface
{
    use ContainerAwareTrait;
    use LoggerAwareTrait;
    use EventDispatcherAwareTrait;

    const AUTH_FAILURE = 'services.auth.auth_failure'; //@todo denne burde hete AUTH_FAILURE_EVENT 
    const REPEATED_LOGIN_BLOCK = 'services.auth.repated_login_block'; //@TODO denne burd ehete REPATED_LOGIN_BLOCK_EVENT
    const MAX_LOGIN_ATTEMPTS = 5; //@TODO vurdere om denne skal leses fra config
   
    const STATUS_AUTHENTICATION_FAILED = -1;
    const STATUS_AUTHENTICATION_NOT_COMPLETED = 0;
    const STATUS_AUTHENTICATION_SUCCESSFUL = 1;
    
    /**
     * Value to define if authentication has completed or not, and if it was succcessful or failed
     * @var int
     */
    private $_authStatus = self::STATUS_AUTHENTICATION_NOT_COMPLETED;
    

    
    /**
     * The request object
     * @var Request
     */
    private $request;
    
    /**
     * Instance of hte user service
     * @var UserService
     */
    private $_userService;
    
    /**
     * The authenticated user
     * @var User
     */
    private $_authenticatedUser;

    /**
     * Instance of the auth token handler
     * @var AuthTokenHandler
     */
    private $_ath;
    
    public function __construct(Request $request, UserService $userService, AuthTokenHandler $ath)
    {
        $this->request = $request;
        $this->_userService = $userService;
        $this->_ath = $ath;
        $this->_lah = $lah;
    }
    
    public function getRemainingLoginAttempts(Request $request, User $user)
    {
        return max([(self::MAX_LOGIN_ATTEMPTS -  $this->getLoginAttemtps($request, $user)), 0]);
    }
    
    // @todo sessionid kan lages på nytt for hver login, her burde jeg legge til database og link til selve brukeren 
    public function getLoginAttemtps(User $user) 
    {
        $loginAttemptsBySession = $this->session->query('login_attempts');
        $loginAttemptsByUser = $user->getLoginAttempts();

        return max([$loginAttemptsBySession, $loginAttemptsByUser]);
    }
    
    public function setLoginAttempts($loginAttempts, User $user)
    {
        $this->session->set('login_attempts', $loginAttempts);
        
        $user->setLoginAttempts($loginAttempts);
        
        $this->logger->info('Login attempts count is now '. $loginAttempts);
    }
    
    public function getMaxAllowedLoginAttempts()
    {
        return self::MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Returns the auth status
     * @param boolean $doAuth Pass false to disable auto-auth
     * @return int
     */
    public function getAuthStatus($doAuth = false)
    {
        if($doAuth === true && $this->isAuthNotCompleted()) {
            $this->auth();
        }
        return $this->_authStatus;
    }
    
    /**
     * Check if authentication has run or not
     * @return boolean
     */
    public function isAuthNotCompleted()
    {
        return (self::STATUS_AUTHENTICATION_NOT_COMPLETED === $this->getAuthStatus());
    }
    
    /**
     * Check if authentication was successful. Will trigger auth if auth has not yet run
     * @return boolean
     */
    public function isAuthSuccess()
    {
        return (self::STATUS_AUTHENTICATION_SUCCESSFUL === $this->getAuthStatus(true));
    }
    
    /**
     * Check if authentication failed. Will trigger auth if auth has not yet run
     * @return boolean
     */
    public function isAuthFailure()
    {
        return (self::STATUS_AUTHENTICATION_FAILED === $this->getAuthStatus(true));
    }
    
    /**
     * Assign a value to the authStatus class parameter
     * @param int $status Any of the defined status constants in this class
     */
    private function _setAuthStatus($status)
    {
        switch($status) {
            case self::STATUS_AUTHENTICATION_SUCCESSFUL:
            case self::STATUS_AUTHENTICATION_FAILED:
            case self::STATUS_AUTHENTICATION_NOT_COMPLETED:
                $this->_authStatus = $status;
                break;
            default:
                throw new \OutOfRangeException('This status code is not recognized');
        }
    }
    
    /**
     * Sets the auth status to indicate a successful auth has completed
     */
    private function _setAuthStatusSuccess()
    {
        $this->_setAuthStatus(self::STATUS_AUTHENTICATION_SUCCESSFUL);
    }
    
    /**
     * Sets the auth status to indicate a failed auth attempt
     */
    private function _setAuthStatusFailure()
    {
        $this->_setAuthStatus(self::STATUS_AUTHENTICATION_FAILED);
    }
    
    public function authVersion2()
    {
        $token = $this->_ath->getToken();
        
        if(!$token) {
            $this->logger->info('No login token defined for request');
            return;
        }

        $this->logger->info('Trying to authenticate as ' . $this->getUser($token)->getUsername());
        
        if(!$token->isValid()) {
            // Login token has been invalidated
        }
        
        if($this->request->getClientAddress() !== $token->getOriginalIP()) {
            // IP has changed... 
        }
        
        // Check if IP has changed x amount of times
        
        // Check if IP has changed compared to last IP
        
        $token->setAuthenticationStatus(LoginToken::STATUS_AUTHENTICATION_SUCCESSFUL);
        
        $this->logger->info('Authentication was successful');
    }
    
    /**
     * Assign a user to the autenticated user variable
     * 
     * If this paramter holds a value, autentication will be skipped
     * @param User $user
     */
    private function _setAuthenticatedUser(User $user)
    {
        $this->logger->info('Autenticated as ' . $user->getUsername());
        $this->_authenticatedUser = $user;
    }
    
    /**
     * Retrieves the authenticated user object. IF no user exists, the authentication method is called to create one
     * 
     * @return User Either a guest, or a member
     */
    public function getUser()
    {
        if ($this->isAuthNotCompleted()) {
            $this->auth();
        }
        return $this->_authenticatedUser;
    }
    
    /**
     * Entry method for user authentication. Will authenticate as member if a login tokin exists, as a guest otherwise
     * 
     * If authentication as member failes th
     * 
     * @return boolean Returns true on successful authentication, false otherwise.
     */
    public function auth(LoginToken $token = null)
    {
        if($token = $token ? : $this->_ath->get()) {
            return $this->authAsMember($token->getUser(), $token) ? : $this->authAsGuest();
        } else {
            return $this->authAsGuest();
        }
    }
    
    /**
     * Autenticate as a guest user
     * @return \bblue\ruby\Entities\User
     */
    public function authAsGuest()
    {
        $this->_setAuthenticatedUser($this->_userService->retrieveGuest());
        $this->_setAuthStatusSuccess();
        return true;
    }
    
    /**
     * Autenticate as a member. This requires that a LoginToken is submitted
     * 
     * The login token created when a login is successful. The application flow would first auth as a guest,
     * then the user controller would check for a login form, if the form was valid the controller will
     * initiate a login command. If the login command is successful a loginToken is submitted to auth. This token
     * is then validated by the auth service before it gets inserted into the database
     * 
     * @param AuthToken $token Optional token if one alrady exists. A new one will otherwise be created
     */
    public function authAsMember(User $user, AuthToken $token = null)
    {
        if($token = $this->_ath->build()) {
            $token->setUser($user);
        }
        
        // Confirm user is same as user in token
        if($user->getId() !== $token->getUser()->getId()) {
            $token->isValid(false);
            $this->_ath->store(); //@todo: _ath er i hovedsak kun laget for å sy sammen session og db, men nå føler jeg at jeg mister kontroll over entities... 
            throw new \Exception('This should not be possible. There is an error in the logic somewhere');
        }
        
        if(!$token->isValid()) {
            $this->logger->warning('Authentication failed as token is not valid');
            return false;
        }
        
        if($this->request->getClientAddress() !== $token->getOriginalIP()) { //@todo Denne må fanges opp
            $this->logger->warning('Authentication failed as client address has changed since initial login');
            return false;
        }
        
        // Check if IP has changed x amount of times
        
        // Check if IP has changed compared to last IP
        
        $this->_setAuthenticatedUser($user);
        $this->_setAuthStatus(self::STATUS_AUTHENTICATION_SUCCESS);
        $token->cleared(true);
        $this->_ath->store();
        
        return true;
    }
    

    
    /**
     * Interactively prompts for input without echoing to the terminal.
     * Requires a bash shell or Windows and won't work with
     * safe_mode settings (Uses `shell_exec`)
     * @link http://www.sitepoint.com/interactive-cli-password-prompt-in-php/
     * @param $prompt The message to echo the user before the password
     */
    private function prompt_silent($prompt = "Enter Password:") {
        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
            file_put_contents(
            $vbscript, 'wscript.echo(InputBox("'
                . addslashes($prompt)
                . '", "", "password here"))');
                $command = "cscript //nologo " . escapeshellarg($vbscript);
                $password = rtrim(shell_exec($command));
                unlink($vbscript);
                return $password;
        } else {
            $command = "/usr/bin/env bash -c 'echo OK'";
            if (rtrim(shell_exec($command)) !== 'OK') {
                trigger_error("Can't invoke bash");
                return;
            }
            $command = "/usr/bin/env bash -c 'read -s -p \""
                . addslashes($prompt)
                . "\" mypassword && echo \$mypassword'";
            $password = rtrim(shell_exec($command));
            echo "\n";
            return $password;
        }
    }
}

final class InvalidCredentialsException extends \UnexpectedValueException {}
final class AuthorizationException extends \OutOfRangeException {}