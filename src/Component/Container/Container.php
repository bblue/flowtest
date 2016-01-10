<?php

namespace bblue\ruby\Component\Container;

use Psr\Log\LoggerInterface;
use ReflectionClass;
use RuntimeException;
use bblue\ruby\Component\Config\ConfigAwareInterface;
use bblue\ruby\Component\Config\ConfigAwareTrait;
use bblue\ruby\Component\Container\Proxy;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\Logger\LoggerAwareTrait;
use bblue\ruby\Traits\StringTester;
use psr\Log\LoggerAwareInterface;

/**
 * 
 * @author Aleksander Lanes
 * @todo skille ut enkeltelementer, slik som auto-wiring
 */
final class Container implements LoggerAwareInterface, ConfigAwareInterface
{
    use LoggerAwareTrait;
    use ConfigAwareTrait;
    
    private $_aClasses = array();
    private $_aDefinitions = array();
    private $_aParameters = array();
    private $_aClassAliasNames = array();
      
    /**
     * @var ClassDefinition
     */
    private $_currentDefinition;
    
    
    public function __construct($config, $logger)
    {
    	$this->setLogger($logger);
    	$this->setConfig($config);
        
        $this->set($logger, 'logger');
        $this->set($config, 'config');
    }
    
    /**
     * Assign an already created object to be managed by the container 
     * 
     * @param unknown $class The object to store in the container
     * @param string $sAlias An optional alias for which to call the class
     * @throws \UnexpectedValueException In case the provided object is not an object
     */
    public function set($object, $alias = '')
    {
        if (!is_object($object)) {
            throw new \UnexpectedValueException(__METHOD__ . ' requires an object');
        }
        
    	$id = strtolower(get_class($object));
    	$this->_aClasses[$id] = $object;
    	
    	if(!empty($alias) && is_string($alias)) {
    		$alias = strtolower($alias);
    		$this->_aClassAliasNames[$alias] = $id;
    	}
 
    	//@todo: Jeg burde injecte disse på get, ikke set
    	$this->injectDependencies($object);
    	
        $this->logger->debug($id . ' stored in container');
        
        return $this;
    }
    
    /**
     * Retrieve an object from the container
     * 
     * First check if we can find the object via an alias name, then check if the object exists, if not, then see if we have a definition object to prepare the object
     * 
     * @param string|Reference $mReference
     * $param bool $required Optional parameter, defaults to true. Setting this to true will throw an exception if the object is not found.
     * @return The object if successful, null otherwise
     */
    public function get($reference, $exceptionOnFailure = false)
    {
        $classKey = $this->getClassKey($reference);
        // Check if alias exists
        if($this->keyIsAliasForClass($classKey)) {
            $classKey = $this->getClassKeyByAlias($classKey);
        }
        // Check if the class is already loaded
        if($this->classKeyIsLoaded($classKey)) {
            return $this->_aClasses[$classKey];
        }
        // Check if class can be loaded as a definition object
        if($this->hasDefinitionObjectByKey($classKey)) {
        	return $this->createFromDefinition($this->getDefinitionObjectByKey($classKey));
        }
        // If we end up here we did not get a hit
        $this->logger->error($msg = 'Container unable to return required object ('.$classKey.')');
        if($exceptionOnFailure) {
            throw new \Exception($msg);
        }
    }
    
    private function getClassKey($reference)
    {
        if(is_string($reference)) {
            $key = $reference;
        } elseif($reference instanceof Reference) {
            $key = $reference->getName();
        } else {
            throw new \Exception('Unable to retrieve a key from provided parameter');
        }
        return strtolower($key);
    }

    /**
     * Get either an alias or a full class name as a reference object.
     * 
     * If no parameters are provided, the container will try to load the current definition object instead
     * 
     * @param string $classToReference
     * @return \bblue\ruby\Component\Container\Reference
     */
    public function getAsReference($classToReference = null)
    {
        // Check if alias exists and call the method again  
        if($classToReference) {
            if(array_key_exists($classToReference, $this->_aClassAliasNames)) {
                return $this->getAsReference($this->_aClassAliasNames[$classToReference]);
            }   
        }
        $this->requireActiveDefinitionObject();
        $classToReference = $this->_currentDefinition->getFullClassName();
        return new Reference($classToReference);
    }
    
    /**
     * @todo Lage denne reflection-capable, så jeg kan videreføre den som constructor argument
     * @param string $classToProxy
     * @return \bblue\ruby\Component\Container\Proxy
     */
    public function getAsProxy($classToProxy = null)
    {
        $id = strtolower(($classToProxy instanceof Reference) ? $classToProxy->getName() : $classToProxy);
        $reference = $this->getAsReference($id);
        return new Proxy($reference, $this);
    }
    
    /**
     * Retrieve current definition object, or a specific one if $class is provided
     * @param string|Reference $class Either a string identifier, or a Reference object
     * 
     * @return \bblue\ruby\Component\Container\ClassDefinition
     */
    public function getAsDefinition($class = null)
    {
        if($class === null) {
            $this->requireActiveDefinitionObject();
            return $this->_currentDefinition;
        }
        $classKey = $this->getClassKey($class);
        $this->getDefinitionObjectByKey($class);
    }
    
    public function setCurrentDefinitionObject($class)
    {
        if(is_string($class)) {
            if ($this->isMagicClassReference($class)) {
                $class = $this->getMagicClassReference($class);
            }
        }
        
        if (!$class instanceof ClassDefinition) {
            throw new \Exception('Provided object is not a class definition object, but a ' . gettype($class));
        }
        
        $this->_currentDefinition = $class;
        
        return $this;
    }
    
    //@todo: Skrive om denne til å benytte seg av en reflection class
    public function injectDependencies($target, \ReflectionClass $reflection = null)
    {
        if($target instanceof ConfigAwareInterface) {
            if(!$target->hasConfig()) {
                $target->setConfig($this->get('config'));
            }
        }
    
        if ($target instanceof ContainerAwareInterface) {
            if(!$target->hasContainer()) {
                $target->setContainer($this);
            }
        }
    
        if($target instanceof LoggerAwareInterface) {
            if(!$target->hasLogger()) {
                $target->setLogger($this->get('logger'));
            }
        }

        if($target instanceof EventDispatcherAwareInterface) {
        	if(!$target->hasEventDispatcher()) {
        	    $target->setEventDispatcher($this->get('eventDispatcher'));
            }
        }        
    }
    
    /**
     * @todo Min class definition burde faktisk væ en reflection object. Det er essensielt samme greie.
     * @todo Denne må skrives om nok en gang
     * @param ClassDefinition $definition
     * @throws \OutOfRangeException
     * @return object
     */
    private function createFromDefinition(ClassDefinition $definition)
    {
        $this->logger->debug('Trying to load definition object ('.$definition->getFullClassName().')');
        // Check for explicit include path
        if(!empty($definition->sIncludePath)) {
            $this->logger->debug('Definition object has include path (' . $definition->sIncludePath . ')');
            require $definition->sIncludePath;
        }
        $defParameters = $definition->getConstructorArguments();
        $reflection = new \ReflectionClass($definition->getFullClassName());
		if(!$refConstructor = $reflection->getConstructor()) {
		    $instance = $reflection->newInstance();
		} else {
    		$missingRefParameters = array();
            // Convert arguments listed in definition to objects  
    	    array_walk($defParameters, function(&$parameter) {
    	        if($parameter instanceof Reference) {
    	            $this->logger->debug('Definition object contains a referenced object ('.$parameter->getName().')');
    	            $parameter = $this->get($parameter);
    	        }
    	    });
    
            // Check if definition object had all the parameters
            $defParamCount = count($defParameters);
            if ($refConstructor->getNumberOfParameters() > $defParamCount) {
                
                // Find the missing parameters
                $refParameters = $refConstructor->getParameters();
                $missingRefParameters = array_diff_key($refParameters, $defParameters);
    
                // Try to assign values to the missing parameters
                foreach($missingRefParameters as $key => &$parameter) {
                    
                    // Check if the parameter should be a class so that we can try to load it from the the container
                    $parameterClass = $parameter->getClass(); // Note! This will cause a fatal error (and trigger __autoload) if the class parameter is not defined
    
                    if($parameterClass) {
                        $this->logger->debug('Constructor argument missing ('. $parameterClass->getName() . ') for definition object ('.$definition->getFullClassName().')');
                        // Attempt to load the parameter from the container
                        if($parameterClass->isInterface()) {
                            $parameter = $this->get($parameter->getName(), !$parameter->isOptional());                            
                        } else {
                            $parameter = $this->get($parameterClass->getName(), !$parameter->isOptional());
                        }
    
                        if($parameter) {
                            $this->logger->debug('Found constructor argument (' . $parameterClass->getName() . ') for definition object ('.$definition->getFullClassName().')');
                        }
                    } elseif($parameter->isOptional()) {
                        $this->logger->notice('Constructor argument missing ('.$parameter->getName() . ') for definition object ('.$definition->getFullClassName().')');
                        unset($missingRefParameters[$key]);
                    } else {
                        throw new Exception('Container unable to create instance of ' . $reflection->getName() . ' due to one or more missing constructor parameters ('.$parameter->getType() . ')');
                    }               
                }
            } elseif ($defParamCount > $refConstructor->getNumberOfParameters())  {
                throw new \OutOfRangeException('Definition object ('.$definition->getFullClassName().') has more constructor arguments for the the class it defines than what the class requires');
            }
    
            // Combine paramter arrays into a final list
            $completeParameterList = array_replace($defParameters, $missingRefParameters);
            ksort($completeParameterList);
            
            // Create the instance of the reflected object
    		$instance = $reflection->newInstanceArgs($completeParameterList);
		}		
		
		if($instance) {
		    $this->logger->debug('Definition ('. $reflection->getName() . ') converted to class instance');

            $this->_aClasses[$definition->getFullClassName()] = $instance;
            // Remove the element from the definition array now that it has been loaded
            unset($this->_aDefinitions[$definition->getFullClassName()]);
		}
        $this->injectDependencies($instance, $reflection);
        
        if($definition->hasMethodCalls()) {
            $aMethodCalls = $definition->getMethodCalls();
            $this->logger->debug('Triggering '.count($aMethodCalls).' methods on definition object ('.$reflection->getName().')');
            foreach($aMethodCalls as $aMethodData) {
                foreach($aMethodData['aParameters'] as &$parameter) {
                    if (is_string($parameter)) {
                        if($this->isMagicParameterString($parameter)) {
                            $parameter = $this->getMagicParameter($parameter);
                        } elseif ($this->isMagicClassReference($parameter)) {
                            $parameter = $this->getMagicClassReference($parameter);
                        }
                    }
                    if($parameter instanceof Reference) {
                        $parameter = ($this->get($parameter->getName())) ? : null;
                    }
                }
                if(is_string($aMethodData['sMethod'])) {
                    $this->logger->debug('Calling ' . $reflection->getName() . '->' . $aMethodData['sMethod'] . '()');
                    call_user_func_array(array($instance, $aMethodData['sMethod']), $aMethodData['aParameters']);
                } elseif (is_callable($aMethodData['sMethod'])) {
                    call_user_func_array($aMethodData['sMethod'], $aMethodData['aParameters']);
                } else {
                    throw new \Exception('Invalid method callback on class definition');
                }
            }
        }
        
        $aParameters = $definition->getParameters();
        foreach ($aParameters as $sParameterName => $value) {
            $instance->$sParameterName = $value;
        }
        return $instance;
    }
    
    /**
     * Register a fully qualified class name without loading it
     * 
     * The method will create a new class definition and save in the in the cache for lazy loading
     * 
     * @param string $sAlias The name for retrieving the instance of the class
     * @param string $sFullClassName The fully qualified class name
     * @return \bblue\ruby\Component\Container\Container
     * @todo skrive om til å hete noe ala $this->define();
     */
    public function register($alias, $sFullClassName, $sIncludePath = '')
    {   
    	$sFullClassName = strtolower($sFullClassName);
    	$alias = strtolower($alias);
    	
        $this->_currentDefinition = new ClassDefinition($sFullClassName);
        
        $this->_currentDefinition->sIncludePath = $sIncludePath;
        
        $this->_aClassAliasNames[$alias] = $sFullClassName;
        $this->_aDefinitions[$sFullClassName] = $this->_currentDefinition;
        
        $this->logger->debug($alias.' defined in container');
        
        return $this;
    }
    
    public function setParameter($sParameterName, $value)
    {
        $this->_aParameters[$sParameterName] = $value;
        return $this;
    }
    
    public function addClassParameter($sParameterName, $value)
    {
        $this->requireActiveDefinitionObject();
        $this->_currentDefinition->setParameter($sParameterName, $value);
        return $this;
    }
    
    /**
     * Check if an active definition object is defined
     * @return boolean Returns true in an object exists in memory, false otherwise
     */
    private function hasActiveDefinitionObject()
    {
        return isset($this->_currentDefinition);
    }
    
    private function requireActiveDefinitionObject()
    {
        // Check if an object is active
        if(!$this->hasActiveDefinitionObject()) {
            // No active objects, throw exception
            throw new RuntimeExcpetion('No current definition object in memory');            
        }
        // An active object exists, return true
        return true;
    }

    public function addConstructorArgument($mArgument, $index = null)
    {
        $this->requireActiveDefinitionObject();

        if(is_string($mArgument)) {
            if($this->isMagicParameterString($mArgument)) {
                $mArgument = $this->getMagicParameter($mArgument);
            } elseif ($this->isMagicClassReference($mArgument)) {
                $mArgument = $this->getMagicClassReference($mArgument);
            }
        }
        $this->_currentDefinition->addConstructorArgument($mArgument, $index);
        return $this;
    }
    
    private function isMagicClassReference($string)
    {
        return (StringTester::startsWith($string, '@'));
    }
    
    private function getMagicClassReference($string)
    {
        $reference = ltrim($string, '@');
        return $this->getAsReference($reference);       
    }
    
    /**
     * Wrapper method around the StringTester class for checking if a string starts and ends with %
     * 
     *  Any string starting and ending with % will be assessed as a magic string, and the script will attempt to substitue the variable with a value
     * 
     * @param string $string The input string
     * @return boolean
     */
    private function isMagicParameterString($string)
    {
        return (StringTester::startsWith($string, '%') && StringTester::endsWith($string, '%'));
    }
    
    private function getMagicParameter($string)
    {
        $sParameterName = trim($string, '%');
        if(!array_key_exists($sParameterName, $this->_aParameters)) {
            $this->logger->debug('Unknown parameter requested (%' . $sParameterName . '%)');
        }
        return $this->_aParameter[$sParameterName];
    }
    
    public function addConstructorArguments(array $aArguments)
    {
        foreach($aArguments as $argument) {
            $this->addConstructorArgument($argument);
        }
    }
    
    /**
     * Add a method call to the stack of commands to call on load
     * 
     * @todo Denne må skille mellom hver eneste load, og kun den første gangen
     * @param unknown $sMethod
     * @param string Optional target class
     * @param array $aParameters
     * @throws RuntimeExcpetion
     * @return \bblue\ruby\Component\Container\Container
     * @todo Delen med targetClass er nesten det samme som get funksjonen. Her må det ryddes og optimaliseres. Selve metoden er uryddig.
     */
    public function addMethodCall($sMethod, array $aParameters = array(), $targetClass = false)
    {
        if($targetClass === false) {
            $this->requireActiveDefinitionObject();
            $definition = $this->_currentDefinition;            
        } elseif($this->isMagicClassReference($targetClass)) {
            $definition = $this->getAsDefinition($targetClass);
        } else {
            if($this->keyIsAliasForClass($targetClass)) {
                $targetClass = $this->getClassKeyByAlias($targetClass);
            }
            $definition = $this->getDefinitionObjectByKey($targetClass);
        }
        $definition->addMethodCall($sMethod, $aParameters);
        return $this;
    }
    
    private function hasDefinitionObjectByKey($key)
    {
        return array_key_exists($key, $this->_aDefinitions);
    }

    private function getDefinitionObjectByKey($key)
    {
        if(!$definition = $this->_aDefinitions[$key]) {
            throw new \Exception('Unknown definition key');
        }
        return $definition;
    }

    private function getClassKeyByAlias($alias)
    {
        if(!$key = $this->_aClassAliasNames[$alias]) {
            throw new \Exception('Unkonwn alias');
        }
        return $key;
    }

    private function classKeyIsLoaded($key)
    {
        return array_key_exists(strtolower($key), $this->_aClasses);
    }

    /**
     * Check if provided key is an alias of a known class
     * @param  string $key The alias
     * @return bool        Returns true if the key is an alias
     */
    public function keyIsAliasForClass($key)
    {
        return array_key_exists(strtolower($key), $this->_aClassAliasNames);
    }

    /**
     * Alias of addMethodCall
     * 
     * @see $this:addMethodCall
     */
    public function onLoad($sMethod, array $aParameters = array())
    {
        return $this->addMethodCall($sMethod, $aParameters);
    }

############ The new stuff #############

    public function register($alias, $fqcn, $filename = null): self
    {
        // Create a new definition object
        $definition = new ClassDefinition($fqcn);
        // Add filename if it is set
        if($filename) {
            $definition->setFilename($filename);
        }
        // Create the associated new class reference
        $reference = new Reference();
        // Configure the reference object
        $reference
            ->setAlias($alias)
            ->setFqcn($fqcn)
            ->setDefinition($definition)
            ->addMethodCall([$this, 'injectDependencies']);
        // Make the new reference object active
        $this->setActiveReference($reference);
        // Return self for method chaining
        return $this;
    }

    public function addCallback($objMethod, array $methodParameters = array(), $obj = null, $var = null): boolean
    {
        // Get the definition object
        $definition = $this->getAsDefinition($var);
        // Prepare callable
        if(!is_object($obj)) {
            $obj = $this->getAsReference($obj);
        }
        // Add callback
        $definition->addCallback($obj, $objMethod, $methodParameters);
        // Return true to indicate success
        return true;
    }

    public function getAsProxy($var = null)
    {
        $reference = $this->getAsReference($var);
        return $this->getProxyByReference($reference);
    }

    public function getProxyByReference(Reference $reference): Proxy
    {
        // No point loading a proxy for an already instanced object
        if($reference->hasClass()) {
            // @todo Trigger error or at least log it
            return false;
        }
        // Create proxy if it does not already exists
        if(!$reference->hasProxy()) {
            // Get a reflection object 
            $reflection = $this->getReflectionByReference($reference);
            // Get and set a proxy object
            $reflection->setProxy($this->getProxyByReflection($reflection));
        }
        // Return the proxy from the reference
        return $reference->getProxy();         
    }

    private function getProxyByReflection(ReflectionClass $reflection): Proxy
    {
        // Build and return the proxy 
        $builder = new ProxyBuilder();
        return $builder->getProxyByReflection($reflection);
    }

    private function createReflection(string $fqcn): ReflectionClass
    {
        return new \ReflectionClass($fqcn);
    }

    public function getAsReflection($var): ReflectionClass
    {
        $reference = $this->getAsReference($var);
        return $this->getReflectionByReference($reference);
    }

    public function getReflectionByReference(Reference $reference): ReflectionClass
    {
        // Confirm we have a reflection
        if(!$reference->hasReflection()) {
            $reflection = $this->createReflection($reference->getFqcn());
            $reference->setReflection($reflection);
        }
        // Return the reflection from the reference
        return $reference->getReflection(); 
    }

    public function getClassByReference(Reference $reference)
    {
        // Only convert if we have not already done so
        if(!$reference->hasClass()) {
            // Get the definition of the class
            $definition = $this->getDefinitionByReference($reference);
            // Convert the definition to a real object
            $class = $this->convertDefinitionToClass($definition);
            // Save the class back to the reference
            $reference->setClass($class);
        }
        // Return the class from the reference
        return $reference->getClass();
    }

    /**
     * Converts provided parameter to a reference object and returns its definition
     * @param  string|Reference|null $var
     * @return ClassDefinition
     */
    public function getAsDefinition($var = null)
    {
        $reference = $this->getAsReference($var);
        return $this->getDefinitionByReference($reference);
    }

    /**
     * Get a definition object from a specific Reference object
     * @param  Reference $reference 
     * @return ClassDefinition               
     */
    public function getDefinitionByReference(Reference $reference): Definition
    {
        // Confirm we have a definition
        if(!$reference->hasDefinition()) {
            throw new \Exception('No definition in reference');
        }
        // Return the definition from the reference
        return $reference->getDefinition();    
    }

    public function convertDefinitionToClass(Definition $definition)
    {
        //@todo vurdere å lage en egen converterklasse for denne
    }

    /**
     * Returns the requested class
     * @param  string|Reference $var A fqcn string, a Reference object, an alias string or a magic reference.
     * @return mixed Returns the requested object
     */
    public function get($var)
    {
        return $this->getAsClass($var);
    }

    /**
     * Returns the requested class
     * 
     * If null is passed to the function the current active reference will be used
     * 
     * @param  [string|Reference] $var [Representation of class to be returned. Can be a string, a Reference object, or null]
     * @return ibject The requested class
     */
    public function getAsClass($var = null)
    {
        // Convert to reference object
        $reference = $this->getAsReference($var);
        // Get the class by the reference and return
        return $this->getClassByReference($reference);
    }

    /**
     * Check if the provided string is a reference known by this container
     * @param  string $string [description]
     * @return bool|Reference Returns the Reference on success, or false if no match was found
     */
    public function hasFqcn(string $fqcn): Reference
    {
        // Loop through the references
        foreach($this->referenceObjects as $reference) {
            // Test if the reference has a fqcn equal to $fqcn
            if($reference->getFqcn() == $fqcn) {
                // Exit loop by returning the reference
                return $reference;
            }
        }
        // Return false to indicate no refernce was found
        return false;
    }

    /**
     * Checks if the provided string is an alias of a reference object
     * @param  string $alias The alias name
     * @return null|Reference Returns the reference object if found, null otherwise
     */
    public function isAlias(string $alias)
    {
        // Loop through the current references
        foreach($this->referenceObjects as $reference) {
            // Check if the refenence has an alias with the same name
            if($reference->hasAlias($alias)) {
                // Exit loop by returning the reference
                return $reference;
            }
        }
        // Return false to indicate no match was found
        return false;
    }

    /**
     * Checks if $var has an alias or not
     * @param  string|Reference $var A fqcn string, a Reference object, an alias string or a magic reference string
     * @return boolean True if the alias was found, false otherwise
     */
    public function hasAlias($var)
    {   
        // Get the reference object and check if it has an alias or not
        return $this->getAsReference($var)->hasAlias();
    }

    /**
     * Get the aliases of $var
     * @param  string|Reference $var A fqcn string, a Reference object, an alias string or a magic reference Pass null to use active reference. Defaults to null
     * @throws Exception Thrown when no alias were returned
     * @return array Array containing all aliases
     */
    public function getAlias($var = null): array
    {
        // Get the reference object
        $reference = $this->getAsReference($var);
        // Check if the reference has an alias
        if(!$reference->hasAlias()) {
            throw new \Exception('Unable to retrieve aliases of reference object');
        }
        // Get the alias from the reference object
        return $reference->getAlias();
    }

    /**
     * Get a single (the first) alias of a reference object
     * @param  string|Reference $var Any class identifer
     * @return string The first element in the alias array
     */
    public function getAsAlias($var = null): string
    {
        // Return the firste element of the alias array
        return reset($this->getAlias());
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
     * @param string|Reference $var The reference to the object to be active
     * @throws Exception Thrown in case the reference is unkown
     * @return self Will return itself to allow method chaining
     */
    public function setActiveReference($var): self 
    {
        // Convert $var to reference
        $reference = $this->getAsReference($var);
        // Find the associated key of the reference
        $key = array_search($reference, $this->referenceObjects, true);
        // Trigger exception if we did not find the key in the reference object array
        if($key === false) {
            throw new \Exception("Could not find this instance of reference object in the container");
        }
        // Assingn the reference as active reference
        $this->activeReference = $this->referenceObjects[$key];
        // Enable method chaining
        return $this;
    }

    /**
     * Converts $var to a reference object
     * 
     * The method will accept a reference object as well, but will simply push it back 
     * 
     * @param  string|Reference $var A fqcn string, a Reference object, an alias string or a magic reference Pass null to use active reference. Defaults to null
     * @throws Exception Thrown when a reference could not be retrieved
     * @return Reference A Reference object
     * @todo For å optimalisere litt burde jeg legge ulke references innen "services" arrays og likende. ala $references['services']. Evrentuelt så kan dette være noe ala repositories, så ha rjeg ulike repos for ulike ting.
     */
    public function getAsReference($var = null): Reference
    {
        // We need to check if the object is a reference object already to support internal functions
        if($var instanceof Reference) {
            return $var;
        }
        // If no parameter is set we will return the current active object
        if($var === null) {
            return $this->getActiveReference();
        } 
        // Check if $var is an alias
        if($this->isAlias($var)) {
            return $this->getReferenceByAlias($var);
        }
        // Check if $var is a fully qualified class name
        if($this->fqcnExists($var)) {
            return $this->getReferenceByFqcn($var);
        }
        // No matches, throw exception
        throw new \Exception('Unable to retrieve reference object');
    }

    /**
     * Method to trigger exception if an active reference object is not set
     * @throws Exception Thrown if no reference objects exists
     * @return bool Returns true on success
     */
    private function requireActiveReferenceObject(): bool
    {
        // Check if we have any reference objects at all
        if(empty($referenceObjects)) {
            throw new \Exception('No reference objects');
        }
        // Confirm there is an active reference object
        if(empty($this->activeReference)) {
            throw new \Exception('No active reference object');
        }
        // Return true to indicate success
        return true;
    }

    /** 
     * Array of reference objects
     * @var Reference[]
     */
    private $referenceObjects;

    /**
     * The current active instance of Reference
     * @var Reference
     */
    private $activeReference;
}