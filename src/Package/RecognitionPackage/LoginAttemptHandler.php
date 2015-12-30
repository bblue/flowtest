<?php

namespace bblue\ruby\Package\RecognitionPackage;

use bblue\ruby\Component\Common\iGenericHandler;
use Doctrine\ORM\EntityManager;
use bblue\ruby\Entities\LoginAttempt;

final class LoginAttemptHandler implements iGenericHandler
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
    
    public function build()
    {
        return new LoginAttempt();
    }
    
}