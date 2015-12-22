<?php
namespace bblue\ruby\Component\Flasher;

use bblue\ruby\Component\Core\SessionHandler;

final class SessionFlasherStorage extends FlasherStorageAbstract implements FlasherStorageInterface
{
    /**
     * @var SessionHandler
     */
    public $session;
    
    public function __construct(SessionHandler $session)
    {
        $this->session = $session;   
    }
    
    private function _prepareString($level = null, $index = null)
    {
        $array = ['flashMessages'];
        if($level) {
            $array[] = $level;
            
            if($index) {
                $array[] = $index;
            }
        }
        
        return implode('.', $array);
    }
    
    /**
     * (non-PHPdoc)
     * @see \bblue\ruby\Component\Flasher\FlasherStorageInterface::get()
     */
    public function get($level, $index = null)
    {
        if($index && $this->inCache($level, $index)) {
            $flash = $this->getFromCache($level, $index);
        } else {
            $array = $this->session->get($this->_prepareString($level, $index));
            foreach($array as $index => $msg) {
                $flash = $this->buildFlashItem($level, $msg);
                $flash->setIndex($index);
                $this->storeInCache($flash);
            }
        }
        
        return $flash;
    }
    
    /**
     * (non-PHPdoc)
     * @see \bblue\ruby\Component\Flasher\FlasherStorageInterface::getAll()
     */
    public function getAll()
    {
        $array = $this->session->query($this->_prepareString());

        if(!is_array($array)) {
            return array();
        }
        
        foreach($array as $level => $flashItems) {                
            foreach($flashItems as $index => $msg) {
                if(!$this->inCache($level, $index)) {
                    $flash = $this->buildFlashItem($level, $msg);
                    $flash->setIndex($index);
                    $this->storeInCache($flash);   
                }
            }    
        }
        return $this->getFromCache();        
    }
    
    /**
     * (non-PHPdoc)
     * @see \bblue\ruby\Component\Flasher\FlasherStorageInterface::store()
     */
    public function store(FlashItem $flash)
    {
        $this->session->append($this->_prepareString($flash->getLevel()), $flash->getMessage());
    }
    
    /**
     * (non-PHPdoc)
     * @see \bblue\ruby\Component\Flasher\FlasherStorageInterface::storeArray()
     */
    public function storeArray(array $flashes)
    {
        
    }
    
    /**
     * (non-PHPdoc)
     * @see \bblue\ruby\Component\Flasher\FlasherStorageInterface::delete()
     */
    public function delete(FlashItem $flash)
    {
        $this->session->delete($this->_prepareString($flash->getLevel(), $flash->getIndex()));
    }
    
    /**
     * (non-PHPdoc)
     * @see \bblue\ruby\Component\Flasher\FlasherStorageInterface::deleteAll()
     */
    public function deleteAll() 
    {
        $this->session->delete($this->_prepareString());
    }
}