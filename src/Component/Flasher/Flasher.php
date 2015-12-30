<?php
namespace bblue\ruby\Component\Flasher;

use bblue\ruby\Component\EventDispatcher\EventDispatcher;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
use bblue\ruby\Component\Logger\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareInterface;

class Flasher implements FlasherInterface, EventDispatcherAwareInterface, LoggerAwareInterface
{
    use EventDispatcherAwareTrait;
    use LoggerAwareTrait;
      
    /**
     * Array containing additional flashing adapters
     * 
     * @var FlasherStorageInterface
     */ 
    private $storage;
    
    /**
     * Internal cache of entries
     * @var array
     */
    private $_cache = array();

    public function __construct(EventDispatcher $ed, LoggerInterface $logger)
    {
        $this->setEventDispatcher($ed);
        $this->setLogger($logger);
        $this->logger->debug(__CLASS__ . ' constructed');
    }
    
    /**
     * Load existing flashes into memory
     * 
     * @param FlasherInterface $adapter The adapter instance
     * @return \bblue\ruby\Component\Flasher\Flasher
     */
    public function load(FlasherStorageInterface $storage)
    {
        $flashes = $storage->getAll();
        $this->storage->storeArray($flashes);
        return $this;
    }

    /**
     * Set the storage mechanism to be used
     * 
     * @param FlasherStorageInterface $storage
     * @return \bblue\ruby\Component\Flasher\Flasher
     */
    public function setStorageMechanism(FlasherStorageInterface $storage)
    {
        $this->storage = $storage;
        return $this;
    }
    
    /**
     * {@inheritDoc}
     */
    public function error($message, array $context = array())
    {
        return $this->flash(FlashLevel::ERROR, $message, $context);
    }
    
    /**
     * {@inheritDoc}
     */
    public function warning($message, array $context = array())
    {
        return $this->flash(FlashLevel::WARNING, $message, $context);
    }
      
    /**
     * {@inheritDoc}
     */
    public function info($message, array $context = array())
    {
        return $this->flash(FlashLevel::INFO, $message, $context);
    }
    
    /**
     * {@inheritDoc}
     */
    public function success($message, array $context = array())
    {
        return $this->flash(FlashLevel::SUCCESS, $message, $context);
    }
    
    /**
     * Method to pass flash message to all adapters
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
     public function flash($level, $msg, array $context = array())
     {
         // Log the flash message
         $this->logger->debug('Flash message to user: [' . $level . '] '. $msg);
         
         // Save entry to internal cache
         $this->storage->store(new FlashItem($level, $msg, $context));
     }
     
     /**
      * {@inheritDoc}
      */
     public function peek($level, $index = null)
     {
         return $this->storage->get($level, $index);
     }
     
     /**
      * {@inheritDoc}
      */
     public function peekAll()
     {
        return $this->storage->getAll();
     }
     
     /**
      * {@inheritDoc}
      */
     public function get($level, $index = null)
     {
         $flash = $this->storage->get($level, $index);
         $this->storage->delete($flash);
         return $flash;
     }

     /**
      * {@inheritDoc}
      */
     public function getAll()
     {
        $flashes = $this->storage->getAll();
        $this->flushAll();
        return $flashes;
     }
     
     /**
      * {@inheritDoc}
      */
     public function flush($level, $index = null)
     {
         $flashes = $this->storage->get($level, $index);
         
         foreach($flashes as $flash) {
             $this->logger->debug('Flushing flash of type ' . $flash->getLevel() . ' and index ' . $flash->getIndex());
             $this->storage->delete($flash);    
         }
     }

     /**
      * {@inheritDoc}
      */
     public function flushAll()
     {
         $this->logger->debug("Flushing all flash messages");
         $this->storage->deleteAll();
     }
}