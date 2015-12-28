<?php
namespace bblue\ruby\Package\RecognitionPackage;

use Psr\Log\LoggerAwareInterface;
use bblue\ruby\Component\Logger\LoggerAwareTrait;
use bblue\ruby\Entities\Guest;
use bblue\ruby\Component\Security\Auth;
use bblue\ruby\Component\EventDispatcher\Event;

final class AnonomyousLogin implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    
    /**
     * The login service to enable logging in
     * @var LoginService
     */
    private $loginService;
    
    /**
     * The user service
     * @var UserService
     */
    private $userService;
    
    /**
     * Constructor does no more than assign parameters
     * @param LoginService $loginService
     */
    public function __construct(LoginService $loginService, UserService $userService)
    {
        $this->loginService = $loginService;
        $this->userService = $userService;
    }
    
    public function handle(Auth $auth)
    {
        $guest = $this->userService->createGuest();
        $token = $this->loginService->createToken($guest);
        $auth->handle($token);
    }
}