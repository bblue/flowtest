<?php

namespace bblue\ruby\Component\Container;

use bblue\ruby\Component\Config\ConfigAwareInterface;
use bblue\ruby\Component\Config\ConfigAwareTrait;
use bblue\ruby\Component\Config\ConfigInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\Logger\iLoggable;
use bblue\ruby\Component\Logger\tLoggerHelper;
use bblue\ruby\Component\Triad\iRubyModel;
use bblue\ruby\Traits\StringHelper;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Class Container
 * @todo Consider creating helper classes for 1) DI, 2) autowiring, 4) Search, 5) other...?
 */
final class Container implements ConfigAwareInterface, iLoggable, iRubyModel
{
    use tLoggerHelper;
    use ConfigAwareTrait;

    /**
     * @var array A list of parameters stored by the container
     * @todo This should be moved to a repository type of class
     */
    private $parameters = [];

    /**
     * @var Reference[] Array of reference objects
     * @todo This should be moved to a repository type of class
     */
    private $referenceObjects = [];

    /**
     * The current active instance of Reference
     * @var Reference
     */
    private $activeReference;

    /**
     * Container constructor.
     * @param  ConfigInterface $config
     * @param LoggerInterface  $logger
     * @param  iProxyBuilder   $proxyBuilder
     * @param  iObjectBuilder  $objectBuilder
     * @throws Exception
     */
    public function __construct(ConfigInterface $config, LoggerInterface $logger, iProxyBuilder $proxyBuilder, iObjectBuilder
    $objectBuilder)
    {
        // Configure and register the logging mechanism
        $this->register($logger, 'logger');
        $this->setLoggerPrefix('container');
        // Load and register the configuration file
        $this->setConfig($config);
        $this->register($config, 'config');
        // Prepare the helper classes
        $this->register($proxyBuilder, 'ProxyBuilder');
        $this->register($objectBuilder, 'ObjectBuilder');
        // Register self repository
        $this->register($this, 'container');
    }

    /**
     * The main entry method of the container. Register a class for storage.
     *
     * A reference object will be created and set as active for further configuration
     *
     * @param callable|string|object $var   The class to be stored
     * @param string                 $alias Optional alias
     * @return Container                    Returns self for method chaining
     * @throws Exception
     */
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

    /**
     * Register a callable in the container
     *
     * The callable may use magic parameters, and therefore only the syntax is checked, not if the callable can
     * actually be called. Provided object will create an active reference for further configuration
     *
     * @param           $callable
     * @param string    $alias     An alias is required
     * @return Container        Returns self for method chaining
     */
    public function registerCallable($callable, string $alias): self
    {
        // Create required objects
        $definition = $this->buildDefinition();
        $reference = $this->buildReference();
        // Configure definition object
        $definition
            ->addConstructorCallback([$this, 'injectDependencies'], [$reference]);
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
     * Registers a class/object in the container.
     *
     * Provided object will create an active reference for further configuration
     *
     * @param        $class
     * @param string $alias If no alias is provided, the unqualified class name will be used
     * @return Container Returns self for method chaining
     */
    private function registerClass($class, string $alias = ''): self
    {
        // Get an alias
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
            ->setUqcn($uqcn);
        // Configure reference object
        $reference
            ->addAlias($alias)
            ->setDefinition($definition)
            ->setClass($class);
        // Inject dependencies as per interfaces
        $this->injectDependencies($class);
        // Eventually register the reference and return
        $this->registerReference($reference);
        return $this;
    }

    /**
     * Register a fully qualified class name in the container
     *
     * Warning! The container will not check that the fqcn can be called until the class is loaded the first time
     *
     * @param string $alias The unqualified class name will be used as alias if no alias is provided
     * @param string $fqcn
     * @param string $filename A filename can be provided to override the autoload function
     * @return Container Returns self for method chaining
     */
    private function registerFqcn(string $fqcn, string $alias = '', string $filename = ''): self
    {
        // Get an alias
        if (empty($alias)) {
            $alias = StringHelper::getClassNameFromFqcn($fqcn);
        }
        // Create required objects
        $definition = $this->buildDefinition();
        $reference = $this->buildReference();
        // Configure definition object
        $definition
            ->setFqcn($fqcn)
            ->addConstructorCallback([$this, 'injectDependencies'], [$reference]);
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

    /**
     * Register a reference object to the container and mark it as active
     * @param Reference $reference
     * @return Container Return self for method chaining
     */
    private function registerReference(Reference $reference): self
    {
        $this->addReferenceToStack($reference);
        $this->setActiveReference($reference);
        return $this;
    }

    /**
     * Add a reference object to the stack
     * @param Reference $reference
     * @return bool True on success, false if reference already exists in the stack
     */
    private function addReferenceToStack(Reference $reference): bool
    {
        // Confirm reference is not already in stack
        if(in_array($reference, $this->referenceObjects, true)) {
            return false;
        }
        $this->referenceObjects[] = $reference;
        return true;
    }

    /**
     * Create a new definition object
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
     * Create a new reflection object by provided fqcn
     * @param string $fqcn
     * @return ReflectionClass
     */
    private function buildReflection(string $fqcn): ReflectionClass
    {
        return new ReflectionClass($fqcn);
    }

    /**
     * Add a callback to a definition or callback object
     *
     * The callback is triggered when the definition or callable is converted to a real object
     *
     * @param       $callable
     * @param array $parameters
     * @param null  $var Optional parameter that will be converted to a reference. Defaults to null.
     * @return Container Return self for method chaining
     */
    public function addConstructorCallback($callable, array $parameters = [], $var = null): self
    {
        // Get the definition object
        $definition = $this->getAsDefinition($var);
        // Build the callable array
        $definition->addConstructorCallback($this->makeCallableArray($callable), $parameters);
        return $this;
    }

    /**
     * Adds a callback to a reference
     * The callback will be called every time the class is loaded
     * @param       $callable
     * @param array $parameters
     * @param null  $var Optional parameter that will be converted to a reference. Defaults to null.
     * @return Container
     */
    public function addLoadingCallback($callable, array $parameters = [], $var = null): self
    {
        // Get the definition object
        $definition = $this->getAsDefinition($var);
        // Build the callable array
        $definition->addLoadingCallback($this->makeCallableArray($callable, $var), $parameters);
        return $this;
    }

    /**
     * Converts the $callable to an callable array, if possible
     * This function enables the addCallback methods to only accept a method name, and then dynamically add the
     * current active reference, as needed

     * @param      $callable
     * @param mixed $var Optional parameter that will be converted to a reference. Defaults to null.
     * @return array The callable array
     */
    private function makeCallableArray($callable, $var = null): array
    {
        // The callable may just be a string (a method name). If that is the case, we add the current active
        // reference as the object
        if(is_string($callable)) {
            $method = $callable;
            $object = $this->getAsReference($var);
            $callable = [$object, $method];
        }
        return $callable;
    }

    /**
     * Converts provided parameter to a reference object and returns its definition
     * @param  mixed $var Optional parameter that will be converted to a reference. Defaults to null.
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
        // If $var is any other object we throw an exception
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

    /**
     * Alias of @this->checkForMagic
     * @see $this->checkForMagic
     */
    public function convertVarToRealVar($var)
    {
        return $this->checkForMagic($var);
    }

    /**
     * Try to convert a variable to its real representative variable manged by the container. IF it exists.
     * @param mixed $var Any variable to be tested for magic
     * @return mixed Returns the converted variable, or the variable if no conversion took place
     */
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

    /**
     * Finds a reference with the fqcn, then converts the reference to a class and returns
     * @param string $fqcn
     * @return mixed The class/object
     */
    public function getClassByFqcn(string $fqcn)
    {
        $reference = $this->getReferenceByFqcn($fqcn);
        return $this->getClassByReference($reference);
    }

    /**
     * Add a parameter to the container
     * @param $parameterName
     * @param $value
     * @return Container Returns self for method chaining
     */
    public function addParameter($parameterName, $value): self
    {
        $this->parameters[$parameterName] = $value;
        return $this;
    }

    /**
     * Wrapper method around the StringHelper class for checking if a string starts and ends with %
     * Any string starting and ending with % will be assessed as a magic string, and the script will attempt to
     * substitute the variable with a value
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
     * Convert a string to a matching parameter stored in the container
     * @param string $string
     * @return mixed The parameter
     * @throws Exception If the parameter was not found
     */
    private function getMagicParameter(string $string)
    {
        $parameterName = trim($string, '%');
        if (!array_key_exists($parameterName, $this->parameters)) {
            throw new \Exception('Requested parameter does not exist');
        }
        return $this->parameters[$parameterName];
    }

    /**
     * Check if a string is magic
     * @param mixed $var
     * @return bool
     * @internal param string $string
     * @todo Check what happens if a reference object is stored as a parameter
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
     * Check if the provided string contains a reference to a Reference object
     * @param string $string
     * @return Reference The reference, if found
     * @throws Exception In case the string is a reference object, but does not match any stored reference objects
     */
    private function getReferenceByMagicString(string $string): Reference
    {
        if($this->isMagicParameter($string)) {
            $string = $this->getMagicParameter($string);
        }
        $alias = ltrim($string, '@');
        if (!$this->isAlias($alias)) {
            throw new Exception('The magic string ('.(is_string($alias) ? $alias : 'UNDEFINED').') does not match any
            aliases');
        }
        $reference = $this->getReferenceByAlias($alias);
        return $reference;
    }

    /**
     * Get the reference with the provided alias
     * @param string $alias
     * @return Reference
     * @throws Exception In case no reference with this alias was found
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
                // Exit
                return true;
            }
        }
        // Return false to indicate no reference was found
        return false;
    }

    /**
     * Return a reference object with the provided fqcn
     * @param string $fqcn
     * @return Reference
     * @throws Exception In case no reference object has the fqcn
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
        throw new Exception('No reference object with this fqcn ('.$fqcn.')');
    }

    /**
     * Get a definition object from a specific Reference object
     * @param  Reference $reference
     * @return ClassDefinition
     * @throws Exception In case the reference has no definition object
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

    /**
     * Add a parameter to the provided $var reference
     * @param mixed     $parameter   the name of parameter
     * @param mixed     $value       the value of the parameter
     * @param null      $var         Optional parameter that will be converted to a reference. Defaults to null.
     * @return Container             Return self for method chaining
     */
    public function addClassParameter($parameter, $value, $var = null): self
    {
        $this->getAsDefinition($var)->setParameter($parameter, $value);
        return $this;
    }

    /**
     * Add a constructor parameter to a reference object
     * @param array      $parameters Array of constructor parameters
     * @param mixed      $var        Optional parameter that will be converted to a reference. Defaults to null.
     * @return Container             Return self for method chaining
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
     * @param mixed      $parameter The parameter to be added
     * @param null       $index     Optional explicit index for the parameter array
     * @param mixed      $var       Optional parameter that will be converted to a reference. Defaults to null.
     * @return Container            Return self for method chaining
     */
    public function addConstructorParameter($parameter, $index = null, $var = null): self
    {
        $this->getAsDefinition($var)->addConstructorParameter($parameter, $index);
        return $this;
    }

    /**
     * Get a single (the first) alias of a reference object
     * @param  mixed $var Optional parameter that will be converted to a reference. Defaults to null.
     * @return string The first element in the alias array
     */
    public function getAsAlias($var = null): string
    {
        // Return the first element of the alias array
        return reset($this->getAlias($var));
    }

    /**
     * Get the aliases stored in a reference object
     * @param mixed $var    Optional parameter that will be converted to a reference. Defaults to null.
     * @return array        Array of class alias
     * @throws Exception In case the reference has no alias
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
     * Returns the requested class and triggers loading callbacks if needed

     * @param  mixed $var Optional parameter that will be converted to a reference. Defaults to null.
     * @return object The requested class
     */
    public function getAsClass($var = null)
    {
        // Convert to reference object
        $reference = $this->getAsReference($var);
        // Get the class by the reference
        $class = $this->getClassByReference($reference);
        // Check for loading callbacks
        $this->triggerLoadingCallbacks($reference);
        return $class;
    }

    /**
     * Return the class that has the alias provided
     * @param string $alias
     * @return mixed
     */
    public function getClassByAlias(string $alias)
    {
        $reference = $this->getReferenceByAlias($alias);
        return $this->getClassByReference($reference);
    }

    /**
     * Trigger class loading callback on the provided reference object
     * @param Reference $reference
     * @return bool Returns true if any callbacks was triggered, false if no callbacks triggered
     */
    private function triggerLoadingCallbacks(Reference $reference): bool
    {
        // Ensure we have a class to trigger callbacks on
        if(!$reference->hasClass()) {
            $this->convertReferenceToClass($reference);
        }
        // Check if we have a definition of the class
        if ($reference->hasDefinition()) {
            $definition = $reference->getDefinition();
            // Trigger callbacks if the definition has any
            if($definition->hasLoadingCallbacks()) {
                $callbacks = $definition->getLoadingCallbacks();
                foreach ($callbacks as $callback) {
                    $this->handleCallback($callback, $reference);
                }
                // Indicate one or more callbacks triggered
                return true;
            }
        }
        // Indicate no callbacks triggered
        return false;
    }

    /**
     * Trigger class constructor callbacks on the provided reference object
     * @param Reference $reference
     * @return bool Returns true if any callbacks was triggered, false if no callbacks triggered
     */
    private function triggerConstructorCallbacks(Reference $reference): bool
    {
        // Ensure we have a class to trigger callbacks on
        if(!$reference->hasClass()) {
            $this->convertReferenceToClass($reference);
        }
        // Check if we have a definition of the class
        if ($reference->hasDefinition()) {
            $definition = $reference->getDefinition();
            // Trigger callbacks if the definition has any
            if($definition->hasConstructorCallbacks()) {
                $callbacks = $definition->getConstructorCallbacks();
                foreach ($callbacks as $callback) {
                    $this->handleCallback($callback, $reference);
                }
                // Indicate one or more callbacks triggered
                return true;
            }
        }
        $reference->hasTriggeredConstructorCallbacks(true);
        // Indicate no callbacks triggered
        return false;
    }

    /**
     * Trigger the specific callback provided
     * @param array     $callback   The callback array
     * @param Reference $reference  Reference to call callback on
     * @return mixed                Returns the return value of the callback
     * @throws Exception            In case no object exists to call callbacks on
     */
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
        return call_user_func_array([$class, $method], $parameters);
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
            $class = $this->getObjectBuilder()->buildFromDefinition($definition, $reflection);
        } else {
            throw new \Exception('Reference object does not have any valid parameters to enable class loading ('.$reference->getAlias().')');
        }
        if(!is_object($class)) {
            throw new \Exception('Reference to class conversion failed');
        }
        // Save the class back to the reference
        $reference->setClass($class);
        // Check if we should trigger callbacks
        if (!$reference->hasTriggeredConstructorCallbacks()) {
            $this->triggerConstructorCallbacks($reference);
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
     * @param  string $var A fqcn string, an alias string or a magic reference.
     * @return mixed Returns the requested object
     */
    public function get(string $var)
    {
        return $this->getAsClassOrProxy($var);
    }

    /**
     * Check the container knows the alias or fqcn provided
     * @param string $var Alias or fqcn
     * @return bool
     */
    public function has(string $var): bool
    {
        return ($this->isAlias($var) || $this->isFqcn($var));
    }

    /**
     * Returns proxy if the object is not yet loaded, otherwise it returns the object
     * @param mixed $var Optional parameter that will be converted to a reference. Defaults to null.
     * @return Proxy|mixed
     * @throws Exception
     * @todo Finish the proxy part
     */
    public function getAsClassOrProxy($var = null)
    {
        return $this->getAsClass($var);
    }

    /**
     * Get a proxy instead of the class itself for lazy loading capabilities
     * @param mixed $var Optional parameter that will be converted to a reference. Defaults to null.
     * @return Proxy
     */
    public function getAsProxy($var = null)
    {
        $reference = $this->getAsReference($var);
        return $this->getProxyByReference($reference);
    }

    /**
     * Get the proxy for given reference object
     * @param Reference $reference
     * @return Proxy
     */
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

    /**
     * Get the proxy builder/factory
     * @return ProxyBuilder
     */
    private function getProxyBuilder(): ProxyBuilder
    {
        return $this->get('ProxyBuilder');
    }

    /**
     * Return a reflection object based on the class definition
     * @param ClassDefinition $definition
     * @return ReflectionClass
     * @throws Exception In case no class could be loaded
     */
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

    /**
     * Get $var as a reflection object
     * @param mixed $var Value that can be converted to a reference object
     * @return ReflectionClass
     */
    public function getAsReflection($var): ReflectionClass
    {
        $reference = $this->getAsReference($var);
        return $this->getReflectionByReference($reference);
    }

    /**
     * Get a reflection object by its reference
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
     * Return all Reference objects as array
     * @return Reference[]
     */
    public function getReferenceObjects(): array
    {
        return $this->referenceObjects;
    }

    /**
     * Inject dependencies into the provided class by the interfaces implemented by the target
     * @param $target
     * @return bool Returns true if anything was injected, false if nothing was injected
     */
    public function injectDependencies($target): bool
    {
        $return = false;
        /** @var ConfigAwareInterface $target */
        if ($target instanceof ConfigAwareInterface) {
            if (!$target->hasConfig()) {
                $target->setConfig($this->get('config'));
                $return = true;
            }
        }
        /** @var ContainerAwareInterface $target */
        if ($target instanceof ContainerAwareInterface) {
            if (!$target->hasContainer()) {
                $target->setContainer($this);
                $return = true;
            }
        }
        /** @var LoggerAwareInterface $target */
        if ($target instanceof LoggerAwareInterface) {
            $target->setLogger($this->get('logger'));
            $return = true;
        }
        /** @var EventDispatcherAwareInterface $target */
        if ($target instanceof EventDispatcherAwareInterface) {
            if (!$target->hasEventDispatcher()) {
                $target->setEventDispatcher($this->get('eventDispatcher'));
                $return = true;
            }
        }
        return $return;
    }
}