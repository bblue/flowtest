<?php
namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Package\RecognitionPackage\Modules\User\Forms\LoginForm;
use Psr\Log\LoggerAwareInterface;
use bblue\ruby\Component\Logger\LoggerAwareTrait;

/**
 * Class to provide native username/password login capabilites
 * 
 * The class relies heavily on the loginService and is in effect only a controller class to provide logic to the $loginForm element.
 * 
 * @author Aleksander Lanes
 *
 */
final class NativeLogin implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /**
     * The login service to enable logging in
     * @var LoginService
     */
    private $loginService;
    
    /**
     * Constructor does no more than assign parameters
     * @param LoginService $loginService
     */
    public function __construct(LoginService $loginService)
    {
        $this->loginService = $loginService;
    }
    
    /**
     * Checks a loginform for a valid username/password combination and returns the user object
     * 
     * @param LoginForm $form
     * @throws InvalidCredentialsException
     * @return boolean
     */
    public function handle(LoginForm $form)
    {
        try {
            // Get the user
            $user = $this->loginService->getUserByUsername($form->getUsername());
            // Register user login attempt
            $this->loginService->registerCurrentLoginAttempt($user);
            // Check number of login attempts for this user and client
            if(!$this->loginService->isBelowLoginAttemptThreshold($user)) {
                $this->logger->notice('Login threshold reached for user');
                $this->loginService->setLoginTimelock($user);
                $form->setError('No login attempts remining. You may not log in for the next 15 minututes.');
                $form->disable();
            }
            if(!$user) {
                $this->logger->notice('Invalid username provided');
                throw new InvalidCredentialsException('User does not exists');
            }
            if(!$user->matchPassword($form->getPassword())) {
                $this->logger->notice('Invalid password provided for user ' . $user->getUsername());
                throw new InvalidCredentialsException('Invalid password');
            }
            return $this->loginService->login($this->loginService->createToken($user)); //@todo kalles finally når jeg returnerer her?
        } catch (InvalidCredentialsException $e) {
            $form->setError(LoginForm::USERNAME_OR_PASSWORD_ERROR);
            $form->getElement('username')->setError();
            $form->getElement('password')->setError();
        } catch (AuthException $e) {
            $form->setError('Unable to complete login due to an authentication failure');
        } finally {
            $form->setRemainingLoginAttempts($this->loginService->getRemainingLoginAttempts());
            $form->setMaxAllowedLoginAttempts($this->loginService->getMaxAllowedLoginAttempts());
        }
    }
}