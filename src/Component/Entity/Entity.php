<?php

namespace bblue\ruby\Component\Entity;

/** @MappedSuperclass */
class Entity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     * @var int
     */
    protected $id;
    
    /**
     * The id of the entity
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }    
    
	/**
	 * Assign a value to the specified field via the corresponding mutator (if it exists);
	 * otherwise, assign the value directly to the protected variable
	 * @param string $name
	 * @param mixed $value
	 * @throws \RuntimeException If the property cannot be assigned
	 * @return Entity Returns instance of itself
	 */
	public function __set($name, $value)
	{
		$mutator = 'set' . ucfirst($this->_converToCamelCase($name));

		if(method_exists($this, $mutator) && is_callable(array($this, $mutator))) {
			$this->$mutator($value);
		} elseif(property_exists($this, $name)) {
			$this->{$name} = $value;
		} else {
		    throw new \BadMethodCallException('Property cannot be assigned to entity (' . $name . ')');
		}
		return $this;
	}
	
	public function __get($property)
	{
	    $mutator = 'get' . ucfirst($this->_converToCamelCase($property));
	    
	    if(method_exists($this, $mutator) && is_callable(array($this, $mutator))) {
	        return $this->$mutator();
	    } elseif(property_exists($this, $property)) {
	        throw new \BadMethodCallException('No mutator for this property exists. Property cannot be retrieved from entity');
	    } else {
	        throw new \BadMethodCallException('Unkonwn entity property');
	    }   
	}
	
	/**
	 * Check if the specified field has been assigned to the entity
	 */
	public function __isset($name)
	{
	    if(property_exists($this, $name)) {
	       return isset($this->$name);
	    }
	}
	
	/**
	 * Unset the specified field from the entity
	 */
	public function __unset($name)
	{
	    if(property_exists($this, $name)) {
	        unset($this->$name);
	    }
	}
	
	private function _converToCamelCase($str)
	{
	    $pattern = "/_[a-z]?/";
	    return preg_replace_callback($pattern,function($matches) {return strtoupper(ltrim($matches[0], "_"));},$str);
	}
}