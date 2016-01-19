<?php

namespace bblue\ruby\Component\Container;

use bblue\ruby\Component\Logger\iLoggable;
use bblue\ruby\Component\Logger\tLoggerHelper;
use Exception;
use Reflection;
use ReflectionClass;

/**
 * Class Reference
 */
final class Reference implements iLoggable
{
    use tLoggerHelper;

    /**
     * @var array
     */
    private $aliases = [];

    /**
     * @var ClassDefinition
     */
    private $definition;

    /**
     * @var object
     */
    private $class;

    /**
     * @var mixed
     */
    private $callable;

    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * @var
     */
    private $fqcn;

    /**
     * @var Proxy
     */
    private $proxy;

    /**
     * @var boolean
     */
    private $hasTriggeredCallbacks = false;

    public function addAlias(string $alias): self
    {
        if (empty($alias)) {
            throw new \Exception('Alias cannot be empty');
        }
        if (!$this->hasAlias($alias)) {
            $this->aliases[] = $this->normalizeAlias($alias);
        }
        return $this;
    }

    /**
     * @param string $alias
     * @return bool
     */
    public function hasAlias(string $alias = null): bool
    {
        return empty($alias) ? !empty($this->getAliases()) : in_array($this->normalizeAlias($alias), $this->getAliases());
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function getAlias()
    {
        return reset($this->aliases);
    }

    private function normalizeAlias(string $alias): string
    {
        if (empty($alias)) {
            throw new \Exception('Alias is empty');
        }
        return strtolower($alias);
    }

    /**
     * @return mixed
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @param $callable
     * @return $this
     */
    public function setCallable($callable)
    {
        $this->callable = $callable;
        return $this;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     * @return Reference
     * @throws Exception
     */
    public function setClass($class): self
    {
        if (!is_object($class)) {
            throw new \Exception('$class is a valid object');
        }
        $this->class = $class;
        return $this;
    }

    /**
     * Get the definition object
     * @return ClassDefinition
     */
    public function getDefinition(): ClassDefinition
    {
        return $this->definition;
    }

    /**
     * @param ClassDefinition $definition
     * @return Reference
     */
    public function setDefinition(ClassDefinition $definition): self
    {
        $this->definition = $definition;
        return $this;
    }

    /**
     * @return Proxy
     */
    public function getProxy(): Proxy
    {
        return $this->proxy;
    }

    /**
     * @param Proxy $proxy
     * @return Reference
     */
    public function setProxy(Proxy $proxy): self
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * @return ReflectionClass
     */
    public function getReflection(): ReflectionClass
    {
        return $this->reflection;
    }

    /**
     * @param ReflectionClass $reflection
     * @return Reference
     */
    public function setReflection(ReflectionClass $reflection): self
    {
        $this->reflection = $reflection;
        return $this;
    }

    /**
     * @param null $callable
     * @return bool
     */
    public function hasCallable($callable = null): bool
    {
        return $this->hasParameter('callable', $callable);
    }

    /**
     * Check if a specific class is present. Will check if any class is set otherwise
     * @param object $class Optional, defaults to null
     * @return bool
     */
    public function hasClass($class = null): bool
    {
        return $this->hasParameter('class', $class);
    }

    /**
     * Checks if a definition object is set
     * @return bool
     */
    public function hasDefinition(): bool
    {
        return isset($this->definition);
    }

    public function hasFqcn(string $fqcn = null): bool
    {
        if (!isset($this->fqcn)) {
            $this->discoverFqcn();
        }
        return $this->hasParameter('fqcn', $this->normalizeFqcn($fqcn));
    }

    private function setFqcn(string $fqcn): self
    {
        if(isset($this->fqcn)) {
            throw new Exception('FQCN is already set for this reference object');
        }
        $this->fqcn = $this->normalizeFqcn($fqcn);
        return $this;
    }

    private function discoverFqcn()
    {
        if($this->hasClass()) {
            $fqcn = $this->getFqcnFromClass();
        } elseif ($this->hasDefinition()) {
            $fqcn = $this->getFqcnFromDefinition();
        } elseif ($this->hasReflection()) {
            $fqcn = $this->getFqcnFromReflection();
        }
        if(!isset($fqcn)) {
            $this->info('No fqcn was detected during discovery');
        } else {
            $this->setFqcn($fqcn);
        }
    }

    private function getFqcnFromClass()
    {
        return get_class($this->getClass());
    }

    private function getFqcnFromDefinition()
    {
        return $this->getDefinition()->getFqcn();
    }

    private function getFqcnFromReflection()
    {
        return $this->getReflection()->getName();
    }

    private function hasParameter($parameter, $value = null): bool
    {
        if (isset($this->$parameter)) {
            return (is_null($value)) ? !empty($this->$parameter) : ($this->$parameter === $value);
        }
        return false;
    }

    private function normalizeFqcn(string $fqcn): string
    {
        return trim(strtolower($fqcn));
    }

    /**
     * @param Proxy|null $proxy
     * @return bool
     */
    public function hasProxy(Proxy $proxy = null): bool
    {
        return $this->hasParameter('proxy', $proxy);
    }

    /**
     * @param null|Reflection $reflection
     * @return bool
     */
    public function hasReflection($reflection = null): bool
    {
        return $this->hasParameter('reflection', $reflection);
    }

    public function hasTriggeredCallbacks(bool $boolean = null): bool
    {
        if (!empty($boolean) && is_bool($boolean)) {
            $this->hasTriggeredCallbacks = $boolean;
        }
        return $this->hasTriggeredCallbacks;
    }
}