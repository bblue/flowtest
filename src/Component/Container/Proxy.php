<?php

namespace bblue\ruby\Component\Container;

final class Proxy implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    public function __construct(Container $container)
    {
        $this->setContainer($container);
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