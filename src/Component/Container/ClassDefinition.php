<?php

namespace bblue\ruby\Component\Container;

final class ClassDefinition
{
    /**
     * @var array
     */
    private $constructorParameters = array();

    /**
     * @var array
     */
    private $parameters = array();

    /**
     * @var string Fully qualified class name (i.e. the class with namespace)
     */
    private $fqcn;

    /**
     * @var Unqualified class name (i.e. the last bit of the fqcn)
     */
    private $uqcn;

    /**
     * @var string
     */
    private $filename;

    /**
     * @var array
     */
    private $callbackStack = array();

    /**
     * @param string $parameterName
     * @param        $value
     * @return ClassDefinition
     */
    public function setParameter(string $parameterName, $value): self
    {
        $this->parameters[$parameterName] = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param $parameterName
     * @return mixed
     */
    public function getParameter($parameterName)
    {
        return $this->parameters[$parameterName];
    }

    /**
     * @param      $parameter
     * @param null $index
     * @return ClassDefinition
     */
    public function addConstructorParameter($parameter, $index = null): self
    {
        if($index) {
            $this->constructorParameters[$index] = $parameter;
        } else {
            $this->constructorParameters[] = $parameter;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getConstructorParameters(): array
    {
        return $this->constructorParameters;
    }

    /**
     * @return array
     */
    public function getCallbacks(): array
    {
        return $this->callbackStack;
    }
    
    /**
     * Check if the definition object has any method calls lined up
     * @return boolean Returns true if one or more method calls are present
     */
    public function hasCallbacks(): bool
    {
        return !empty($this->callbackStack);
    }

    /**
     * @param string $filename
     * @return bool
     */
    public function hasFilename(string $filename = ''): bool
    {
        return $this->hasParameter('filename', $filename);
    }

    /**
     * @param      $parameter
     * @param null $value
     * @return bool
     */
    private function hasParameter($parameter, $value = null): bool
    {
        if(isset($this->$parameter)) {
            return (is_null($value)) ? !empty($this->$parameter) : ($this->$parameter === $value);
        }
        return false;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return ClassDefinition
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function setFqcn(string $fqcn): self
    {
        $this->fqcn = $fqcn;
        return $this;
    }

    public function hasFqcn(string $fqcn = null): bool
    {
        return $this->hasParameter('fqcn', $fqcn);
    }

    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function addCallback($callable, array $parameters = []): self
    {
        if(!is_callable($callable, true)) {
            throw new \Exception('$callable is not of correct syntax');
        }
        $this->callbackStack[] = [$callable,$parameters];
        return $this;
    }

    public function setUqcn(string $uqcn): self
    {
        $this->uqcn = $uqcn;
        return $this;
    }

    public function hasUqcn(string $uqcn = null): bool
    {
        return $this->hasParameter('uqcn', $uqcn);
    }

    public function getUqcn(): string
    {
        return $this->uqcn;
    }
}