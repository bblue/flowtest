<?php

namespace bblue\ruby\Package\DatabasePackage;

use Doctrine\DBAL\Logging\SQLLogger;
use bblue\ruby\Component\Logger\Psr3LoggerHandler;

final class DoctrineLogger implements SQLLogger
{
    private $handler;
    
    public function __construct(Psr3LoggerHandler $handler)
    {
        $this->handler = $handler;
    }
    
    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->handler->debug($sql, [$params, $types]);
    }
    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
    }
}