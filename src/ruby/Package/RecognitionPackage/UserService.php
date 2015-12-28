<?php

namespace bblue\ruby\Package\RecognitionPackage;

use Doctrine\ORM\EntityManager;
use bblue\ruby\Entities\User;
use bblue\ruby\Component\EventDispatcher\EventDispatcher;
use bblue\ruby\Package\DatabasePackage\DoctrineEvent;
use bblue\ruby\Component\Core\iUserProvider;
use bblue\ruby\Entities\Guest;

final class UserService implements iUserProvider
{

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
    
    public function __construct(EntityManager $em, EventDispatcher $ed)
    {
        $this->em = $em;
        $this->ed = $ed;
    }
    
    /**
     * (non-PHPdoc)
     * @see \bblue\ruby\Component\Core\iUserProvider::getByUsername()
     */
    public function getByUsername($username)
    {
        if($user = $this->em->getRepository('bblue\ruby\Entities\User')->findOneBy(['username'=>$username])) {
            return $user;
        }
    }
    
    /**
     * (non-PHPdoc)
     * @see \bblue\ruby\Component\Core\iUserProvider::getById()
     */
    public function getById($id)
    {
        if($user = $this->em->find('bblue\ruby\Entities\User', $id)) {
            return $user;
        }
    }
    
    public function getGuest()
    {
        return $this->em->find('bblue\ruby\Entities\User', Guest::GUEST_ID);
    }
    
    public function createGuest()
    {
        return new \bblue\ruby\Entities\Guest();
    }
    
    public function assertUsernameIsAvailable($username)
    {
        return !($this->getByUsername($username));
    }
}