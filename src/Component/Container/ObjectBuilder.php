<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 11.01.2016
 * Time: 11:30
 */

namespace bblue\ruby\Component\Container;

use bblue\ruby\Component\Logger\iLoggable;
use bblue\ruby\Component\Logger\tLoggerHelper;
use Exception;

class ObjectBuilder implements ContainerAwareInterface, iLoggable, iObjectBuilder
{
    use ContainerAwareTrait;
    use tLoggerHelper;

    public function __construct()
    {
        $this->setLoggerPrefix('container');
    }

    /**
     * @param ClassDefinition  $definition
     * @param \ReflectionClass $reflection
     * @return object
     * @throws Exception
     */
    public function buildFromDefinition(ClassDefinition $definition, \ReflectionClass $reflection)
    {
        // Check for explicit include path
        if($definition->hasFilename()) {
            $filename = $definition->getFilename();
            $this->debug('Definition object has include path (' . $filename . ')');
            require $filename;
        }
        // Get the fqcn
        if(!$definition->hasFqcn()) {
            throw new Exception('Definition object is missing fqcn');
        }
        $fqcn = $definition->getFqcn();
        // Build the instance object
        $instance = $this->buildInstance($reflection, $definition);

        $this->debug('Definition ('. $fqcn . ') converted to class instance');
        $aParameters = $definition->getParameters();
        foreach ($aParameters as $sParameterName => $value) {
            $instance->$sParameterName = $this->container->checkForMagic($value);
        }
        return $instance;
    }

    //@todo: Denne hører vel muligens hjemme i container
    private function convertParametersToClasses(array $parameters = []): array
    {
        foreach($parameters as $key => $parameter) {
            $parameterValue = $this->container->checkForMagic($parameter);//@todo denne kalles undøvendig når
            // paramter allerede er en refernence. Det kan nok fikses ved å kalle metoden igjen internt
            if($parameterValue instanceof Reference) {
                $parameterValue = $this->container->getClassByReference($parameterValue);
            }
            $parameters[$key] = $parameterValue;
        }
        return $parameters;
    }

    private function buildInstance(\ReflectionClass $reflection, ClassDefinition $definition)
    {
        if($reflection->getConstructor()) {
            $instance = $this->buildInstanceWithConstructor($reflection, $definition);
        } else {
            $instance = $this->buildInstanceWithoutConstructor($reflection);
        }
        return $instance;
    }

    private function buildInstanceWithConstructor(\ReflectionClass $reflection, ClassDefinition $definition)
    {
        $constructor = $reflection->getConstructor();
        // Convert arguments listed in definition to objects
        $defCstrParams = $this->convertParametersToClasses($definition->getConstructorParameters());
        // Count the number of parameters provided
        $defCstrParamsCount = count($defCstrParams);
        $refCstrParamsCount = $constructor->getNumberOfParameters();
        // Check if definition object had all the parameters
        $foundRefCstrParams = [];
        if ($refCstrParamsCount > $defCstrParamsCount) {
            // Find the missing parameters
            /** @var \ReflectionParameter[] $refCstrParams */
            $refCstrParams = $constructor->getParameters();
            $missingRefCstrParams = array_diff_key($refCstrParams, $defCstrParams);
            $typeHint = null;
            // Try to assign values to the missing parameters
            foreach($missingRefCstrParams as $key => $reflectionParameter) {
                // Check if the reflectionParameter should be a class so that we can try to load it from the the container
                /** @var \ReflectionParameter $reflectionParameter */
                $typeHint = ($reflectionParameter->hasType()) ? $reflectionParameter->getType()->__toString() : null; //@todo vurdere om
                //  tostring er nødvendig
                switch($typeHint) {
                    case 'array':case 'string':case 'int':case 'bool':
                        // these cannot be handled by container at current time
                        if($reflectionParameter->isOptional()) {
                            $this->notice('Constructor argument missing ('.$reflectionParameter->getName() . ') for
                             definition object ('.')');
                        } else {
                            throw new Exception('Container unable to create instance of ' . $reflection->getName() . ' due to one or more missing constructor parameters ('.$reflectionParameter->getType() . ')');
                        }
                        break;
                    case null:
                        // Try to load the object via its name from the container
                        if($this->container->isAlias($reflectionParameter->getName())) {
                            $foundRefCstrParams[$key] = $this->container->getClassByAlias($reflectionParameter->getName());
                        } else {
                            throw new Exception('Container unable to create instance of ' . $reflection->getName() . ' due to one or more missing constructor parameters ('.$reflectionParameter->getType() . ')');
                        }
                        break;
                    default:
                        // If we end up here we may be looking for an object (http://php
                        //.net/manual/en/reflectionparameter.getclass.php)
                        /* preg_match('/\[\s\<\w+?>\s([\w]+)/s', $reflectionParameter->__toString(), $matches);
                        // $reflectionParameter = $matches[1] ?? null;*/
                        //$parameterClass= $reflectionParameter->getClass(); //ReflectionParameter::getClass() will cause a
                        // fatal error (and trigger __autoload) if the class required by the reflectionParameter is not defined.
                        // Try to load the object via its name from the container
                        if($this->container->isFqcn($typeHint)) {
                            $foundRefCstrParams[$key] = $this->container->getClassByFqcn($typeHint);
                        } elseif($this->container->isAlias($reflectionParameter->getName())) {
                            $foundRefCstrParams[$key] = $this->container->getClassByAlias($reflectionParameter->getName());
                        } else {
                            throw new Exception('Container unable to create instance of ' . $reflection->getName() .
                                ' due to one or more missing constructor parameters ('.$typeHint . ')');
                        }
                        break;
                }
            }
        } elseif ($defCstrParamsCount > $refCstrParamsCount)  {
            throw new \OutOfRangeException('Definition object ('.$definition->getFqcn().') has more constructor
            arguments for the the class it defines than what the class requires');
        }
        // Combine reflectionParameter arrays into a final list
        $completeParameterList = $foundRefCstrParams + $defCstrParams;
        ksort($completeParameterList);
        // Create the instance of the reflected object
        return $reflection->newInstanceArgs($completeParameterList);
    }

    private function buildInstanceWithoutConstructor(\ReflectionClass $reflection)
    {
        return $reflection->newInstance();
    }

    public function buildFromCallable($callable)
    {
        return $callable($this->container);
    }
}