<?php

namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Component\Core\SessionHandler;
use Doctrine\ORM\EntityManager;
use bblue\ruby\Entities\LoginToken;
use bblue\ruby\Component\EventDispatcher\EventDispatcher;
use bblue\ruby\Package\DatabasePackage\DoctrineEvent;
use bblue\ruby\Package\RecognitionPackage\Entities\Guest;
use Doctrine\Common\EventManager;
use bblue\ruby\Component\Common\iGenericHandler;
use bblue\ruby\Entities\AuthToken;

final class AuthTokenHandler implements iGenericHandler
{
    /**
     * @var SessionHandler
     */
    private $session;
    
    /**
     * The doctrine entity manager
     * @var EntityManager
     */
    private $em;
    
    /**
     * The event dispatcher
     * @var EventDispatcher
     */
    private $ed;
    
    /**
     * The login token identification 
     * @var string
     */
    private $_tokenId;
    
    /**
     * The auth token object associated with this instance
     * @var AuthToken
     */
    private $_token;
    
    const AUTH_TOKEN_VAR_NAME = 'authTokenID';
    const STORAGE_SYSTEM_FLUSHED = DoctrineEvent::FLUSHED;
    const STORAGE_SYSTEM_SCHEDULE_FLUSH = DoctrineEvent::SCHEDULE_FLUSH;
    
    public function __construct(SessionHandler $session, EntityManager $em, EventDispatcher $ed)
    {
        $this->session = $session;
        $this->em = $em;
        $this->ed = $ed;
    }

    private function _getID()
    {
        if(!isset($this->_tokenId)) {
            $tokenId = $this->session->query(self::AUTH_TOKEN_VAR_NAME);
            $this->_setID($tokenId);
        }
        return $this->_tokenId;
    }
    
    private function _setID($tokenID)
    {
        $this->_tokenId = !isset($tokenID) ? false : $tokenID;
    }
    
    public function build()
    {
        if(isset($this->_token)) {
            throw new \Exception(__CLASS__ . ' is already associated with a specific auth token');
        }
        return new AuthToken();
    }
    
    private function _hasToken()
    {
        if(isset($this->_token)) {
            throw new \Exception('No token associated with auth token handler');
        }
    }
    
    public function store()
    {
        if($this->_hasToken()) {
            $this->em->persist($this->_token);
            $this->em->flush($this->_token);
            $this->session->set(self::AUTH_TOKEN_VAR_NAME, $this->_token->id);         
        }
    }
    
    public function invalidate()
    {
        //persist
        //flush
    }
    
    /**
     * Get a token object
     * 
     * @param string $id Optional id to specify which token to obtain. Defaults to null, which will return the token id in session.
     * @return \bblue\ruby\Entities\LoginToken A login token object, if one was found. False otherwise.
     */
    public function get($tokenId = null)
    {
        if($this->_hasToken()) {
            return $this->_token;
        } elseif($tokenId = ($tokenId) ? : $this->_getID()) {
            return $this->em->find('bblue\ruby\Entities\LoginToken', $tokenId);
        }
    }
}