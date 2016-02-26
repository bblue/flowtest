<?php

namespace bblue\ruby\Component\Security;

use bblue\ruby\Component\Core\AbstractRequest as Request;
use bblue\ruby\Component\EventDispatcher\EventDispatcher;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
use bblue\ruby\Component\Logger\tLoggerAware;
use bblue\ruby\Component\Request\iInternalRequest;
use bblue\ruby\Entities\User;
use Psr\Log\LoggerAwareInterface;

/**
 * The authentication class accepts an auth token and finalizes the authentication process
 *
 * @author Aleksander Lanes
 * @todo finne ut hva som skjer dersom jeg IKKE sletter session storage ved behov. Når jeg skriver dette slettes ikke "gamle" tokens. Dette kan nok skape problemer.
 * @todo burde jeg ikke ha en eksplisitt "handle" et eller nnet sted, slik at jeg definitivt kjører auth? Slik det står nå vil jeg unngå auth dersom jeg bre ikke kaller getUser
 */
final class Auth implements EventDispatcherAwareInterface, LoggerAwareInterface
{
    use EventDispatcherAwareTrait;
    use tLoggerAware;

    /**
     * An auth token storage system
     * @var iAuthStorage
     */
    private $storage;

    /**
     * The auth token
     * @var iAuthToken
     */
    private $token;

    /**
     * The request made to the site
     * @var iInternalRequest
     */
    private $request;

    /**
     * @param iAuthStorage     $storage
     * @param iInternalRequest $request
     * @param EventDispatcher  $ed
     * @internal param iUserProvider $userProvider
     */
    public function __construct(iAuthStorage $storage, iInternalRequest $request, EventDispatcher $ed)
    {
        $this->storage  = $storage;
        $this->setEventDispatcher($ed);
        $this->request = $request;
    }

    /**
     * Retrieves the authenticated user object
     *
     * @return User
     */
    public function getUser()
    {
        $token = $this->_getToken();
        if(!$token->hasUser()) {
            throw new \Exception('Unable to retreive user from token object');
        }
        $this->logger->debug('Auth service returning user object');
        return $token->getUser();
    }

    /**
     * Returns the stored auth token
     *
     * If not value is stored, a dispatcher event is triggered and loading a token is tried once more
     *
     * @throws \Exception
     * @return AuthToken
     */
    private function _getToken()
    {
        // Check if we have already a token in object memory
        if($this->token) {
            return $this->token;
        }
        // Try to load a token from token storage
        if($token = $this->storage->getToken()) {
            $this->logger->info('Auth token found in auth storage');
            $this->handle($token);
            return $this->token;
        }
        // As a last attempt, create a signal to allow external parties to add a token //@todo dette er muligens et digert sikkerhetshull. Jeg exposer hele auth systemet
        $this->logger->info('No auth token stored in system. Sending auth beacon.');
        if($this->eventDispatcher->dispatch(AuthEvent::NO_AUTH_TOKEN, ['auth'=>$this])) {
            $this->logger->debug('Auth beacon was picked up');
            if(isset($this->token)) {
                return $this->token;
            }
        }
        throw new \Exception("Unable to retrieve a login token");
    }

    /**
     * Main method of this class. Handles an auth token.
     * The token and associated user object is checked for validity before the token is stored in the storage mechanism
     * @param iAuthToken $token
     * @throws \Exception
     */
    public function handle(iAuthToken $token)
    {
        $this->logger->info('Auth token received. Trying to authenticate');
        $tokenValidator = new AuthTokenValidator($token, $this->request); //@todo: lage en stack med disse som kan støtte ulike tokens, ala symfony
        $tokenValidator->validate();
        if (!$token->isValid()) {
            throw new AuthException(implode("\n", $tokenValidator->getErrors()));
        }
        $this->logger->notice('Authenticated as ' . $token->getUser()->getUsername());
        // Store the token in object memory
        $this->_setToken($token);
        // Store the token in token storage mechanism
        $this->storage->storeToken($token);
        return true;
    }

    private function _setToken(iAuthToken $token)
    {
        $this->token = $token;
    }
}

final class AuthException extends \RuntimeException {};