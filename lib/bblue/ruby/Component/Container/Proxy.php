<?php

namespace bblue\ruby\Component\Container;

final class Proxy implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    /**
     * @var Reference
     */
    private $reference;
    
    /**
     * Instance of the class to be proxied
     */
    private $proxiedClass;
    
    public function __construct(Reference $reference, Container $container)
    {
        $this->reference = $reference;
        $this->setContainer($container);
    }
    
    private function _loadProxiedClass()
    {
        return $this->proxiedClass = $this->container->get($this->reference);
    }
    
    private function _getProxiedClass()
    {
        return $this->proxiedClass ? : $this->_loadProxiedClass();
    }
    
    public function __call($name, array $args = array()) 
    {
        return call_user_func_array([$this->_getProxiedClass(), $name], $args);
    }
        
    public static function __callStatic($name, array $arguments = array())
    {
        throw new \Exception('Denne funksjonen er ikke implementert enda');
    }
    
    public function __isset($name)
    {
        throw new \Exception('Denne funksjonen er ikke implementert enda');
    }
    
    public function __unset($name)
    {
        throw new \Exception('Denne funksjonen er ikke implementert enda');
    }
    
    public function __set($name, $value)
    {
        throw new \Exception('Denne funksjonen er ikke implementert enda');
    }
    
    public function __get($name)
    {
        throw new \Exception('Denne funksjonen er ikke implementert enda');
    }
}