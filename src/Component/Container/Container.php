<?php

namespace bblue\ruby\Component\Container;

use bblue\ruby\Component\Config\ConfigAwareInterface;
use psr\Log\LoggerAwareInterface;
use bblue\ruby\Traits\StringTester;
use RuntimeException;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\Logger\LoggerAwareTrait;
use bblue\ruby\Component\Config\ConfigAwareTrait;
use Psr\Log\LoggerInterface;
use bblue\ruby\Caller\Caller;

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
    public function get($mReference, $required = true)
    {
        $return = null; //The object to be returned
        
        $id = strtolower(($mReference instanceof Reference) ? $mReference->getName() : $mReference);
        
        // Check if alias exists and call the method again
        if(array_key_exists($id, $this->_aClassAliasNames)) {
        	return $this->get($this->_aClassAliasNames[$id], $required);
        }

        // Check if the class is already loaded
        if(array_key_exists($id, $this->_aClasses)) {
            $return = $this->_aClasses[$id];
        } elseif (array_key_exists($id, $this->_aDefinitions)) {
        	$this->_aClasses[$id] = $this->createFromDefinition($this->_aDefinitions[$id]);
        	$return = $this->_aClasses[$id];
        	// Remove the element from the definition array now that it has been loaded
        	// @todo denne må flyttes til en egen method + den må gjøres smartere ved noe ala "loadedDefinitions" eller [loaded] = true
            unset($this->_aDefinitions[$id]);
        }

        if($required && !is_object($return)) {
            throw new \Exception('The container was unable to retrive an object instance for the reference ' . $id);
        } else {
            $this->logger->debug('Container returning ' . $id);
            return $return;
        }
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
        if(!$classToReference) {
            $this->hasActiveDefinitionObject();
            $classToReference = $this->_currentDefinition->getFullClassName();
        } else {
            // Check if alias exists and call the method again
            if(array_key_exists($classToReference, $this->_aClassAliasNames)) {
                return $this->getAsReference($this->_aClassAliasNames[$classToReference]);
            }            
        }
        
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
        $id = strtolower(($class instanceof Reference) ? $class->getName() : $class);
        
        if(!$id) {
            $this->hasActiveDefinitionObject();
            return $this->_currentDefinition;
        } else {
            if(array_key_exists($id, $this->_aDefinitions)) {
                return $this->_aDefinitions[$id];
            }
        }
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
        
        $this->_currentDefinition = $object;
        
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
    
    private function runMethods($object, $methods)
    {
        foreach($aMethodCalls as $aMethodData) {
            foreach($aMethodData['aParameters'] as &$parameter) {
                if($parameter instanceof Reference) {
                    $parameter = $this->get($parameter->getName());
                } elseif(is_string($parameter)) {
                    if($this->isMagicParameterString($parameter)) {
                        $parameter = $this->getMagicParameter($parameter);
                    }
                }
            }
            call_user_func_array(array($instance, $aMethodData['sMethod']), $aMethodData['aParameters']);
        } 
    }
    
    /**
     * @todo Min class definition burde faktisk være en reflection object. Det er essensielt samme greie.
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
                    
                    // Check if the paramter should be a class so that we can try to load it from the the container
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
		}
		
		$aMethodCalls = $definition->getMethodCalls();

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
                    $parameter = $this->get($parameter->getName());
                }
            }
            $this->logger->debug('Triggering methods on definition object ('.get_class($instance)."->{$aMethodData['sMethod']}()");
            call_user_func_array(array($instance, $aMethodData['sMethod']), $aMethodData['aParameters']);
        }
        
        $aParameters = $definition->getParameters();
        foreach ($aParameters as $sParameterName => $value) {
            $instance->$sParameterName = $value;
        }
        
        $this->injectDependencies($instance, $reflection);
        
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
        
        $caller = new Caller();
        $this->_currentDefinition->definedBy($this->getAsReference($caller->class));
        
        $this->_aClassAliasNames[$alias] = $sFullClassName;
        $this->_aDefinitions[$sFullClassName] = $this->_currentDefinition;
        
        $this->logger->debug($alias.' defined in container');
        $this->logger->debug('Definition details:', (array)$caller);
        
        return $this;
    }
    
    public function setParameter($sParameterName, $value)
    {
        $this->_aParameters[$sParameterName] = $value;
        return $this;
    }
    
    public function addClassParameter($sParameterName, $value)
    {
        $this->hasActiveDefinitionObject();
        $this->_currentDefinition->setParameter($sParameterName, $value);
        return $this;
    }
    
    private function hasActiveDefinitionObject()
    {
        if(empty($this->_currentDefinition)) {
            $msg = 'No current definition object in memory';
            $this->logger->debug($msg);
        
            throw new RuntimeExcpetion($msg);
        }
    }
    
    public function addConstructorArgument($mArgument, $index = null)
    {
        $this->hasActiveDefinitionObject();

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
        if(array_key_exists($sParameterName, $this->_aParameters)) {
            return $this->_aParameter[$sParameterName];
        } else {
            $this->logger->debug('Unknown parameter requested (%' . $sParameterName . '%)');
        }
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
    public function addMethodCall($sMethod, array $aParameters = array(), $targetClass = null)
    {
        if(!$targetClass) {
            $this->hasActiveDefinitionObject();
            $this->_currentDefinition->addMethodCall($sMethod, $aParameters);            
        } else {
            $id = strtolower($this->getMagicClassReference($targetClass)->getName());

            // Check if alias exists
            if(array_key_exists($id, $this->_aClassAliasNames)) {
            	$id = $this->_aClassAliasNames[$id];
            }

            if(array_key_exists($id, $this->_aClasses)) {
                call_user_func_array(array($this->_aClasses[$id], $sMethod), $aParameters);
            } elseif (array_key_exists($id, $this->_aDefinitions)) {
            	$this->_aDefinitions[$id]->addMethodCall($sMethod, $aParameters);
            }
        }

        return $this;
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
    
    private function hasDefinitionObject()
    {
        if(empty($this->_currentDefinition)) {
            $msg = 'No current definition object in memory';
            $this->logger->debug($msg);
            
            throw new RuntimeExcpetion($msg);
        }
    }
}