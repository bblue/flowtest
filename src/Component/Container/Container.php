<?php

namespace bblue\ruby\Component\Container;

use bblue\ruby\Component\Config\ConfigAwareInterface;
use bblue\ruby\Component\Config\ConfigAwareTrait;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\Logger\iLoggable;
use bblue\ruby\Component\Logger\tLoggerHelper;
use bblue\ruby\Traits\StringHelper;
use Exception;
use Psr\Log\LoggerAwareInterface;
use ReflectionClass;

/**
 * Class Container
 * @todo Consider creating helper classes for 1) DI, 2) autowiring, 3) Storage, 4) Search, 5) other...?
 */
final class Container implements ConfigAwareInterface, iLoggable
{
    use tLoggerHelper;
    use ConfigAwareTrait;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * Array of reference objects
     * @var Reference[]
     */
    private $referenceObjects = [];

    /**
     * The current active instance of Reference
     * @var Reference
     */
    private $activeReference;

    public function __construct($config, $logger, $proxyBuilder, $objectBuilder)
    {
        $this->setLogger($logger);
        $this->setLoggerPrefix('container');
        $this->setConfig($config);

        $this->register($config, 'config');
        $this->register($logger, 'logger');
        $this->register($proxyBuilder, 'ProxyBuilder');
        $this->injectDependencies($objectBuilder);
        $this->register($objectBuilder, 'ObjectBuilder');
        $this->register($this, 'container');
    }

    public function register($var, string $alias = ''): self
    {
        if (is_callable($var)) {
            return $this->registerCallable($var, $alias);
        }
        if (is_object($var)) {
            return $this->registerClass($var, $alias);
        }
        if (is_string($var)) {
           return $this->registerFqcn($var, $alias);
        }
        throw new \Exception('No registration methods that can handle this request');
    }

    public function registerCallable($callable, string $alias): self
    {
        // Create required objects
        $definition = $this->buildDefinition();
        $reference = $this->buildReference();
        // Configure definition object
        $definition
            ->addCallback([$this, 'injectDependencies'], [$reference]);
        // Configure reference object
        $reference
            ->addAlias($alias)
            ->setDefinition($definition)
            ->setCallable($callable);
        // Eventually register the reference and return
        $this->registerReference($reference);
        return $this;
    }

    /**
     * @param        $class
     * @param string $alias
     * @return Container
     * @throws Exception
     */
    private function registerClass($class, string $alias = ''): self
    {
        $fqcn = get_class($class);
        $uqcn = StringHelper::getClassNameFromFqcn($fqcn);
        if (empty($alias)) {
            $alias = $uqcn;
        }
        // Create required objects
        $definition = $this->buildDefinition();
        $reference = $this->buildReference();
        // Configure definition object
        $definition
            ->setFqcn($fqcn)
            ->setUqcn($uqcn)
            ->addCallback([$this, 'injectDependencies'], [$reference]);
        // Configure reference object
        $reference
            ->addAlias($alias)
            ->setDefinition($definition)
            ->setClass($class);
        // Eventually register the reference and return
        $this->registerReference($reference);
        return $this;
    }

    /**
     * @param string $alias
     * @param string $fqcn
     * @param string $filename A filename can be provided to override the autoload function
     * @return Container
     * @throws Exception
     */
    private function registerFqcn(string $fqcn, string $alias = '', string $filename = ''): self
    {
        if (empty($alias)) {
            $alias = StringHelper::getClassNameFromFqcn($fqcn);
        }
        // Create required objects
        $definition = $this->buildDefinition();
        $reference = $this->buildReference();
        // Configure definition object
        $definition
            ->setFqcn($fqcn)
            ->addCallback([$this, 'injectDependencies'], [$reference]);
        // Add filename if it is set
        if (!empty($filename)) {
            $definition->setFilename($filename);
        }
        // Configure reference object
        $reference
            ->addAlias($alias)
            ->setDefinition($definition);
        // Eventually register the reference and return
        $this->registerReference($reference);
        return $this;
    }

    private function registerReference(Reference $reference): self
    {
        $this->addReferenceToStack($reference);
        // Make the new reference object active
        $this->setActiveReference($reference);
        // Return self for method chaining
        return $this;
    }

    private function addReferenceToStack(Reference $reference)
    {
        // Confirm reference is not already in stack
        if(in_array($reference, $this->referenceObjects, true)) {
            throw new Exception('Reference object is already in stack');
        }
        $this->referenceObjects[] = $reference;
    }

    /**
     * Create a new definition object. Requires a fqcn
     * @return ClassDefinition
     */
    private function buildDefinition(): ClassDefinition
    {
        return new ClassDefinition();
    }

    /**
     * Create a new reference object
     * @return Reference
     */
    private function buildReference(): Reference
    {
        return new Reference();
    }

    /**
     * @param string $fqcn
     * @return ReflectionClass
     */
    private function buildReflection(string $fqcn): ReflectionClass
    {
        return new ReflectionClass($fqcn);
    }

    /**
     * Add a callback to a definition or callback object
     * The callback is triggered when the definition or callable is converted to a real object
     * @param       $callable
     * @param array $parameters
     * @param null  $var
     * @return Container|bool
     * @throws Exception
     * @todo Lage en atLoadingCallback som trigger når en klasse loades /i.e. get
     */
    public function addConstructorCallback($callable, array $parameters = [], $var = null): self
    {
        // Get the definition object
        $definition = $this->getAsDefinition($var);
        // Build the callable array if required
        if(is_string($callable)) {
            $callable = [$this->getAsReference($var), $callable];
        }
        $definition->addCallback($callable, $parameters);
        return $this;
    }

    /**
     * Converts provided parameter to a reference object and returns its definition
     * @param  string|Reference|null $var
     * @return ClassDefinition
     */
    public function getAsDefinition($var = null): ClassDefinition
    {
        return $this->getDefinitionByReference($this->getAsReference($var));
    }

    /**
     * Converts $var to a reference object
     * The method will accept a reference object as well, but will simply push it back
     * @param  string|Reference $var A fqcn string, a Reference object, an alias string or a magic reference Pass null
     *                               to use active reference. Defaults to null
     * @throws Exception Thrown when a reference could not be retrieved
     * @return Reference A Reference object
     */
    public function getAsReference($var = null): Reference
    {
        // We need to check if the object is a reference object already to support some internal functions
        if ($var instanceof Reference) {
            return $var;
        }
        // If no parameter is set we will return the current active object
        if ($var === null) {
            return $this->getActiveReference();
        }
        if (is_object($var)) {
            throw new Exception('Objects cannot be handled by getAsReference()');
        }
        // Check for magic references
        if ($this->isMagicReference($var)) {
            return $this->getReferenceByMagicString($var);
        }
        // Check if $var is an alias
        if ($this->isAlias($var)) {
            return $this->getReferenceByAlias($var);
        }
        // Check if $var is a fully qualified class name
        if ($this->isFqcn($var)) {
            return $this->getReferenceByFqcn($var);
        }
        // No matches, throw exception
        throw new ReferenceNotFoundException('Unable to retrieve reference object ('. $var . ')', $var);
    }

    public function checkForMagic($var)
    {
        if (is_string($var)) {
            if ($this->isMagicParameter($var)) {
                return $this->checkForMagic($this->getMagicParameter($var));
            } elseif ($this->isMagicReference($var)) {
                $var = $this->getReferenceByMagicString($var);
            }
        }
        return $var;
    }

    public function getClassByFqcn(string $fqcn)
    {
        $reference = $this->getReferenceByFqcn($fqcn);
        return $this->getClassByReference($reference);
    }

    public function setParameter($parameterName, $value)
    {
        $this->parameters[$parameterName] = $value;
        return $this;
    }

    /**
     * Wrapper method around the StringHelper class for checking if a string starts and ends with %
     *  Any string starting and ending with % will be assessed as a magic string, and the script will attempt to
     *  substitute the variable with a value
     * @param mixed $var
     * @return bool
     * @internal param string $string The input string
     */
    private function isMagicParameter($var): bool
    {
        if (is_string($var)) {
            return (StringHelper::startsWith($var, '%') && StringHelper::endsWith($var, '%'));
        }
        return false;
    }

    /**
     * @param string $string
     * @return bool
     */
    private function getMagicParameter(string $string): bool
    {
        $parameterName = trim($string, '%');
        if (!array_key_exists($parameterName, $this->parameters)) {
            $this->debug('Unknown parameter requested (%' . $parameterName . '%)');
        }
        return $this->parameters[$parameterName];
    }

    /**
     * Check if a string is magic
     * @param mixed $var
     * @return bool
     * @internal param string $string
     */
    private function isMagicReference($var): bool
    {
        if($this->isMagicParameter($var)) {
            $var = $this->getMagicParameter($var);
        }
        if (is_string($var)) {
            return (StringHelper::startsWith($var, '@'));
        }
        return false;
    }

    /**
     * @param string $string
     * @return Reference
     * @throws Exception
     */
    private function getReferenceByMagicString(string $string): Reference
    {
        if($this->isMagicParameter($string)) {
            $string = $this->getMagicParameter($string);
        }
        $alias = ltrim($string, '@');
        if (!$this->isAlias($alias)) {
            throw new Exception('The magic string does not match any aliases');
        }
        $reference = $this->getReferenceByAlias($alias);
        return $reference;
    }

    /**
     * @param string $alias
     * @return Reference
     * @throws Exception
     */
    public function getReferenceByAlias(string $alias): Reference
    {
        // Loop through the references
        /** @var Reference $reference */
        foreach ($this->referenceObjects as $reference) {
            // Test if the reference has a alias equal to $fqcn
            if ($reference->hasAlias($alias)) {
                // Exit loop by returning the reference
                return $reference;
            }
        }
        // If we end up here no match was found
        throw new Exception('No reference object with this alias ('.$alias.')');
    }

    /**
     * Returns the current active reference object
     * @return Reference
     */
    public function getActiveReference(): Reference
    {
        // Require an active reference object
        $this->requireActiveReferenceObject();
        // Return the current array pointer. This will default to the first key in the array
        return $this->activeReference;
    }

    /**
     * Assigns the provided reference object as active. The object must be in the referenceObjects array.
     * @param Reference $reference
     * @return Container
     * @throws Exception Thrown in case the reference is unknown
     * @internal param Reference|string $var The reference to the object to be active
     */
    public function setActiveReference(Reference $reference): self
    {
        // Find the associated key of the reference
        $key = array_search($reference, $this->referenceObjects, true);
        // Trigger exception if we did not find the key in the reference object array
        if ($key === false) {
            throw new Exception("Could not find this instance of reference object in the container");
        }
        // Assign the reference as active reference
        $this->activeReference = $this->referenceObjects[$key];
        // Enable method chaining
        return $this;
    }

    /**
     * Method to trigger exception if an active reference object is not set
     * @throws Exception Thrown if no reference objects exists
     * @return bool Returns true on success
     */
    private function requireActiveReferenceObject(): bool
    {
        // Check if we have any reference objects at all
        if (empty($this->referenceObjects)) {
            throw new Exception('No reference objects');
        }
        // Confirm there is an active reference object
        if (empty($this->activeReference)) {
            throw new Exception('No active reference objects');
        }
        // Return true to indicate success
        return true;
    }

    /**
     * Checks if the provided string is an alias of a reference object
     * @param string $alias
     * @return bool
     */
    public function isAlias($alias): bool
    {
        if(is_string($alias)) {
            // Loop through the current references
            foreach ($this->referenceObjects as $reference) {
                // Check if the reference has an alias with the same name
                if ($reference->hasAlias($alias)) {
                    // Exit loop by returning the reference
                    return true;
                }
        }
        }
        // Return false to indicate no match was found
        return false;
    }

    /**
     * Check that a reference with the fqcn exists
     * @param string $fqcn
     * @return bool
     */
    public function isFqcn(string $fqcn): bool
    {
        // Loop through the references
        /** @var Reference $reference */
        foreach ($this->referenceObjects as $reference) {
            // Test if the reference has a fqcn equal to $fqcn
            if ($reference->hasFqcn($fqcn)) {
                // Exit loop by returning the reference
                return true;
            }
        }
        // Return false to indicate no reference was found
        return false;
    }

    /**
     * @param string $fqcn
     * @return Reference
     * @throws Exception
     */
    public function getReferenceByFqcn(string $fqcn): Reference
    {
        // Loop through the references
        /** @var Reference $reference */
        foreach ($this->referenceObjects as $reference) {
            // Test if the reference has a fqcn equal to $fqcn
            if ($reference->hasFqcn($fqcn)) {
                // Exit loop by returning the reference
                return $reference;
            }
        }
        // If we end up here no match was found
        throw new Exception('No reference object with this fqcn');
    }

    /**
     * Get a definition object from a specific Reference object
     * @param  Reference $reference
     * @return ClassDefinition
     * @throws Exception
     */
    public function getDefinitionByReference(Reference $reference): ClassDefinition
    {
        // Confirm we have a definition
        if (!$reference->hasDefinition()) {
            throw new Exception('No definition in reference');
        }
        // Return the definition from the reference
        return $reference->getDefinition();
    }

    public function addClassParameter($parameter, $value, $var = null)
    {
        $this->getAsDefinition($var)->setParameter($parameter, $value);
        return $this;
    }

    /**
     * @param array                 $parameters
     * @param null|Reference|string $var Will be converted to a reference object
     * @return Container
     */
    public function addConstructorParameters(array $parameters, $var = null): self
    {
        foreach ($parameters as $parameter) {
            $this->addConstructorParameter($parameter, null, $var);
        }
        return $this;
    }

    /**
     * Add a constructor argument to a definition object
     * @param                       $parameter
     * @param null                  $index
     * @param null|Reference|string $var
     * @return Container
     */
    public function addConstructorParameter($parameter, $index = null, $var = null): self
    {
        $this->getAsDefinition($var)->addConstructorParameter($parameter, $index);
        return $this;
    }

    /**
     * Get a single (the first) alias of a reference object
     * @param  string|Reference $var Any class identifier
     * @return string The first element in the alias array
     */
    public function getAsAlias($var = null): string
    {
        // Return the first element of the alias array
        return reset($this->getAlias($var));
    }

    /**
     * @param null $var
     * @return array
     * @throws Exception
     */
    public function getAlias($var = null): array
    {
        // Get the reference object
        /** @var Reference $reference */
        $reference = $this->getAsReference($var);
        // Check if the reference has an alias
        if (!$reference->hasAlias()) {
            throw new Exception('Unable to retrieve aliases of reference object');
        }
        // Get the alias from the reference object
        return $reference->getAliases();
    }

    /**
     * Returns the requested class AND triggers callbacks if needed
     * If null is passed to the function the current active reference will be used
     * @param  string|Reference|null $var Representation of class to be returned
     * @return object The requested class
     */
    public function getAsClass($var = null)
    {
        // Convert to reference object
        $reference = $this->getAsReference($var);
        // Get the class by the reference and return
        $class = $this->getClassByReference($reference);
        return $class;
    }

    public function getClassByAlias(string $alias)
    {
        $reference = $this->getReferenceByAlias($alias);
        return $this->getClassByReference($reference);
    }

    private function triggerCallbacks(Reference $reference)
    {
        if(!$reference->hasClass()) {
            $this->convertReferenceToClass($reference);
        }
        if ($reference->hasDefinition()) {
            if ($callbacks = $reference->getDefinition()->getCallbacks()) {
                foreach ($callbacks as $callback) {
                    $this->handleCallback($callback, $reference);
                }
            }
        }
        $reference->hasTriggeredCallbacks(true);
    }

    private function handleCallback(array $callback, Reference $reference)
    {
        list($callable, $parameters) = $callback;
        if(is_array($callable)) {
            list($class, $method) = $callable;
        } else {
            if(!$reference->hasClass()) {
                throw new Exception('Cannot trigger callback. Referenced class does not contain an object');
            }
            $class = $reference->getClass();
            $method = $callable;
        }
        foreach ($parameters as &$parameter) {
            $parameter = $this->checkForMagic($parameter);
            if($parameter instanceof Reference) {
                $parameter = $this->getClassByReference($parameter);
            }
        }
        if($this->isMagicReference($method)) {
            $method = $this->getReferenceByMagicString($method);
        }
        if($method instanceof Reference) {
            $method = $this->getClassByReference($method);
        }
        if($this->isMagicReference($class)) {
            $class = $this->getReferenceByMagicString($class);
        }
        if($class instanceof Reference) {
            $class = $this->getClassByReference($class);
        }
        call_user_func_array([$class, $method], $parameters);
    }

    /**
     * Returns the class associated with the reference
     * @param Reference $reference
     * @return mixed
     */
    public function getClassByReference(Reference $reference)
    {
        // Only convert if we have not already done so
        if (!$reference->hasClass()) {
            $this->convertReferenceToClass($reference);
        }
        return $reference->getClass();
    }

    /**
     * Convert a reference object to its associated class
     * @param Reference $reference
     * @return object
     * @throws Exception If conversion failed
     */
    private function convertReferenceToClass(Reference $reference)
    {
        if ($reference->hasClass()) {
            $class = $reference->getClass();
        } elseif ($reference->hasCallable()) {
            $class = $this->convertCallableToClass($reference->getCallable());
        } elseif ($reference->hasDefinition()) {
            $reflection = $this->getReflectionByReference($reference);
            $definition = $this->getDefinitionByReference($reference);
            $class = $this->convertDefinitionToClass($definition, $reflection);
        } else {
            throw new \Exception('Reference object does not have any valid parameters to enable class loading ('.$reference->getAlias().')');
        }
        if(!is_object($class)) {
            throw new \Exception('Reference to class conversion failed');
        }
        // Save the class back to the reference
        $reference->setClass($class);
        // Check if we should trigger callbacks
        if (!$reference->hasTriggeredCallbacks()) {
            $this->triggerCallbacks($reference);
        }
        return $class;
    }

    /**
     * Returns the output of callable via the object builder
     * @param $callable
     * @return mixed
     * @throws Exception
     */
    private function convertCallableToClass($callable)
    {
        $builder = $this->getObjectBuilder();
        $class = $builder->buildFromCallable($callable);
        if(!is_object($class)) {
            throw new \Exception('$builder->buildFromCallable() did not return an object');
        }
        return $class;
    }

    /**
     * Get the object builder
     * @return ObjectBuilder
     * @throws Exception
     */
    private function getObjectBuilder(): ObjectBuilder
    {
        return $this->get('ObjectBuilder');
    }

    /**
     * Alias of $this->getAsClassOrProxy, but $var is not optional
     * @param  string$var A fqcn string, an alias string or a magic reference.
     * @return mixed Returns the requested object
     */
    public function get(string $var)
    {
        return $this->getAsClassOrProxy($var);
    }

    public function has(string $var): bool
    {
        return ($this->isAlias($var) || $this->isFqcn($var));
    }

    /**
     * Returns proxy if the object is not yet loaded, otherwise it returns the object
     * @param null|string|Reference $var
     * @return Proxy|mixed
     * @throws Exception
     * @todo Finish the proxy part
     */
    public function getAsClassOrProxy($var = null)
    {
        return $this->getAsClass($var);
    }

    public function getAsProxy($var = null)
    {
        $reference = $this->getAsReference($var);
        return $this->getProxyByReference($reference);
    }

    public function getProxyByReference(Reference $reference): Proxy
    {
        // Create proxy if it does not already exists
        if (!$reference->hasProxy()) {
            $builder = $this->getProxyBuilder();
            $proxy = $builder->buildFromReference($reference);
            $reference->setProxy($proxy);
        }
        // Return the proxy from the reference
        return $reference->getProxy();
    }

    private function getProxyBuilder(): ProxyBuilder
    {
        return $this->get('ProxyBuilder');
    }

    /**
     * Turns definition into class via reflection object
     * @param ClassDefinition $definition
     * @param ReflectionClass $reflection
     * @return object
     * @throws Exception
     */
    private function convertDefinitionToClass(ClassDefinition $definition, ReflectionClass $reflection)
    {
        $builder = $this->getObjectBuilder();
        $class = $builder->buildFromDefinition($definition, $reflection);
        if(!is_object($class)) {
            throw new Exception('$this->convertReflectionToClass() did not return an object');
        }
        return $class;
    }

    private function convertDefinitionToReflection(ClassDefinition $definition): ReflectionClass
    {
        if (!$definition->hasFqcn()) {
            throw new Exception('Definition does not have a fqcn, unable to convert to reflection');
        }
        $fqcn = $definition->getFqcn();
        if(!class_exists($fqcn)) {
            throw new Exception('Definition does not contain a valid fqcn (' . $fqcn . ')');
        }
        return $this->buildReflection($fqcn);
    }

    public function getAsReflection($var): ReflectionClass
    {
        $reference = $this->getAsReference($var);
        return $this->getReflectionByReference($reference);
    }

    /**
     * @param Reference $reference
     * @return ReflectionClass
     */
    private function getReflectionByReference(Reference $reference): ReflectionClass
    {
        // Confirm we have a reflection
        if (!$reference->hasReflection()) {
            $reflection = $this->convertDefinitionToReflection($reference->getDefinition());
            $reference->setReflection($reflection);
        }
        // Return the reflection from the reference
        return $reference->getReflection();
    }

    /**
     * @return Reference[]
     */
    public function getReferenceObjects()
    {
        return $this->referenceObjects;
    }

    public function injectDependencies($target)
    {
        /** @var ConfigAwareInterface $target */
        if ($target instanceof ConfigAwareInterface) {
            if (!$target->hasConfig()) {
                $target->setConfig($this->get('config'));
            }
        }
        /** @var ContainerAwareInterface $target */
        if ($target instanceof ContainerAwareInterface) {
            if (!$target->hasContainer()) {
                $target->setContainer($this);
            }
        }
        /** @var LoggerAwareInterface $target */
        if ($target instanceof LoggerAwareInterface) {
            $target->setLogger($this->get('logger'));
        }
        /** @var EventDispatcherAwareInterface $target */
        if ($target instanceof EventDispatcherAwareInterface) {
            if (!$target->hasEventDispatcher()) {
                $target->setEventDispatcher($this->get('eventDispatcher'));
            }
        }
    }
}