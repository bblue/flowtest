<?php
namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Component\Core\AbstractRequest;
use bblue\ruby\Component\Core\iUserProvider;
use bblue\ruby\Component\Logger\tLoggerAware;
use bblue\ruby\Component\Security\aAuthTokenProvider;
use bblue\ruby\Component\Security\AuthTokenFactory;
use bblue\ruby\Entities\Guest;
use Psr\Log\LoggerAwareInterface;

final class AnonomyousAuthTokenProvider extends aAuthTokenProvider implements LoggerAwareInterface
{
    use tLoggerAware;
    
    /**
     * The login service to enable logging in
     * @var LoginService
     */
    private $authTokenFactory;

    /**
     * The request object
     * @var AbstractRequest
     */
    private $request;
    
    /**
     * A user provider
     * @var iUserProvider
     */
    private $userProvider;

    /**
     * Constructor does no more than assign parameters
     * @param authTokenFactory $authTokenFactory
     */
    public function __construct(AuthTokenFactory $authTokenFactory, AbstractRequest $request, iUserProvider $userProvider)
    {
        $this->authTokenFactory = $authTokenFactory;
        $this->request = $request;
        $this->userProvider = $userProvider;
    }
    
    public function getToken()
    {
        $token = $this->authTokenFactory->buildAnonomyousToken();
        $token = $this->prepareToken($token, $this->request, $this->userProvider->getById(Guest::GUEST_ID));
        return $token;
    }
}