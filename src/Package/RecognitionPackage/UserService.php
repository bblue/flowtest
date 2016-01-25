<?php

namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Component\Core\iUserProvider;
use bblue\ruby\Entities\Member;
use bblue\ruby\Entities\User;
use Doctrine\ORM\EntityManager;

final class UserService implements iUserProvider
{

    /**
     * The doctrine entity manager
     * @var EntityManager
     */
    private $em;
    
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * (non-PHPdoc)
     * @see \bblue\ruby\Component\Core\iUserProvider::getByUsername()
     * @return User|bool|null|object
     */
    public function getByUsername($username)
    {
        if($user = $this->em->getRepository('bblue\ruby\Entities\User')->findOneBy(['username'=>$username])) {
            return $user;
        }
    }

    public function buildMember(array $parameters)
    {
        $user = new Member();
        foreach($parameters as $key => $value) {
            $user->$key = $value;
        }
        return $user;
    }

    public function addUser(User $user)
    {
        if(!$this->assertUsernameIsAvailable($user->getUsername())) {
            throw new \Exception('User already exists');
        }
        $this->em->persist($user);
        $this->em->flush($user);
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
    
    public function assertUsernameIsAvailable($username)
    {
        return !($this->getByUsername($username));
    }
}