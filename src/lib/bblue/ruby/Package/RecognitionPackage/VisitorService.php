<?php

namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Container\ContainerAwareTrait;
use bblue\ruby\Entities\Visitor;
use bblue\ruby\Entities\User;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
use bblue\ruby\Package\DatabasePackage\DoctrineEvent;
use Psr\Log\LoggerAwareInterface;
use bblue\ruby\Component\Logger\LoggerAwareTrait;
use Doctrine\ORM\EntityManager;
use bblue\ruby\Component\EventDispatcher\Event;

class VisitorService implements ContainerAwareInterface, EventDispatcherAwareInterface, LoggerAwareInterface
{
    use ContainerAwareTrait;
    use EventDispatcherAwareTrait;
    use LoggerAwareTrait;
    
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
     * Get the visitor of this request
     * @return bblue\ruby\Entities\Visitor
     */
    public function getCurrentVisitor()
    {
        if($visitor = $this->container->get('visitor')) {
            return $visitor;
        }

        $session = $this->container->get('request')->getSession();
        $em = $this->container->get('entityManager');
        
        if($visitorId = $session->query('visitor_id')) {
            if($visitor = $em->find('bblue\ruby\Entities\Visitor', $visitorId)) {
                $this->logger->info('Visitor obtained from session');
            }
        }

        if(!$visitor instanceof Visitor) {
            $visitor = new Visitor();
            $this->registerVisitor($visitor);
        }

        $this->setCurrentVisitor($visitor);
        
        return $visitor; /** @todo: Dette kan være en bug. Mulig jeg må få visitor fra container nå */
    }
    
    public function setCurrentVisitor(Visitor $visitor)
    {
        $this->container->get('request')->getSession()->set('visitor_id', $visitor->getId());
        $this->container->set($visitor, 'visitor');
    }
    
    /**
     * Get the logged in user instance. If no user exists, a guest object is created
     * @return User;
     */
    public function getCurrentUser()
    {
        $visitor = $this->getCurrentVisitor();
    
        if($visitor->hasUser()) {
            $user = $visitor->getUser();
        } else {
            $user = $this->createGuestUser();
            $this->setCurrentUser($user);
        }
        
        return $user;
    }
     
    private function createGuestUser()
    {
        return $this->em->find('bblue\ruby\Entities\User', User::GUEST_ID);
    }
    
    public function registerVisitor(Visitor $visitor)
    {
        $visitor->setLastSeen(new \DateTime());
        
        $this->em->persist($visitor);
        //$em->flush($visitor);
        $this->eventDispatcher->dispatch(DoctrineEvent::SCHEDULE_FLUSH);
        
        $this->logger->info('New visitor registered to database');
        
        return $visitor;
    }

    public function setCurrentUser(User $user)
    {
        $visitor = $this->getCurrentVisitor();
        $visitor->setUser($user);
        // $this->container->set($user, 'User'); @todo dersom denne er commentet kan den fjernes
    }
    
    public function updateVisitorEntry()
    {
        
    }
    
    public function authenticate(Visitor $visitor)
    {
        // Build a visitor object
        $visitor = $this->getCurrentVisitor();

        // Check if we are already logged in
        if($visitor->isLoggedIn()) {
            return true;
        }

        // Build a user object
        $user = $this->entityFactory->build('user');

        // Check that we have received values
        if(empty($username) || empty($password)) {
            return $user;
        }

        // Get the requested user from database
        $user->Username = $username;
        $this->dataMapperFactory
            ->build('user')
            ->fetch($user);

        if($aErrors = $user->hasError()) {
            foreach($aErrors as $sMessage) 	{
                $this->log->createLogEntry($sMessage, $visitor, 'warning', true);
            }
            return $user;
        }
    
        // Make sure user exists
        if(!$user->id) {
            $sMessage = 'Username or password incorrect';
            $this->log->createLogEntry($sMessage, $visitor, 'warning', true);
            return $user;
        }
    
        // Check password
        if(!$user->matchPassword($password)) 	{
            $sMessage = 'Username or password incorrect';
            $this->log->createLogEntry($sMessage, $visitor, 'warning', true);
            return $user;
        }
    
        // Update the entity
        $visitor->user_id = $user->id;
        $visitor->user = $user;
    
        if(!$visitor->isLoggedIn()) {
            // Something is very wrong...
            throw new \Exception ('Error in user login system');
        }
        //@todo: dersom jeg oppdaterer $visitor->user_id etter Ã¥ ha hentet $visitor->user sÃ¥ blir det krÃ¸ll.
        //@todo: Det mÃ¥ IKKE fungerer Ã¥ $entity->entity2->value = $verdi, da dette ikke vil kunne lagres som det skal. Eventuelt sÃ¥ mÃ¥ jeg lage en funksjon som faktisk vil kunne lagre de sakene, men det virker tungvint...
    
        if($this->registerVisitor($visitor)) {
            $sMessage = 'You are now logged in as ' . $visitor->user->Firstname;
            $this->log->createLogEntry($sMessage, $visitor, 'success', true);
            return $user;
        } else {
            throw \Exception('Registration of visitor with userID ('.$visitor->user_id.') in database failed');
        }
    }
}