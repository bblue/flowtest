<?php
namespace bblue\ruby\Component\Validation;

final class Validation {

	// Define required variables
	public $errors = array();
	private $validation_rules = array();
	public $sanitized = array();
	private $data = array();
	
	// Defaults
	const DEFAULT_USERNAME_MIN_LENGTH = 5;
	const DEFAULT_USERNAME_MAX_LENGTH = 32;
	const DEFAULT_USERNAME_REGEX = "/[a-zA-Z0-9]/";
	const DEFAULT_PASSWORD_MIN_LENGTH = 5;
	const DEFAULT_PASSWORD_MAX_LENGTH = 256;

    public function addSource(array $source) {
        $this->data = $source;
        return $this;
    }

    public function resetValidationRules(){
    	$this->validation_rules = array();
    }

    public function resetSource(){
    	$this->data = array();
    }

   	private function resetErrors(){
   		$this->errors = array();
   	}

   	private function resetSanitized(){
   		$this->sanitized = array();
   	}

    public function resetAll(){
    	$this->resetSource();
    	$this->resetValidationRules();
    	$this->resetErrors();
    	$this->resetSanitized();
    }

    public function hasError()
    {
    	return (!empty($this->errors)) ? $this->errors : false;
    }

    public function getErrors($key = null)
    {
        if(!is_null($key)) {
            return isset($this->errors[$key]) ? $this->errors[$key] : null;
        }
    	return $this->errors;
    }

	public function validate(){
		// We use the php built-in ArrayIterator to prepare the variables
		foreach( new \ArrayIterator($this->validation_rules) as $var=>$opt){
			if($opt['required'] == true){
				$this->is_set($var);
			}

			// Trim whitespace from beginning and end of variable
			if( array_key_exists('trim', $opt) && $opt['trim'] == true ){
				$this->data[$var] = trim( $this->data[$var] );
			}

			switch($opt['type']){
				default:
					throw new Exception('Validation type ' . $opt['type'] . ' does not exist');
					break;
				case 'email':
					$this->validateEmail($var, $opt['required'], $opt['regex']);
					if(!array_key_exists($var, $this->errors)){
						$this->sanitizeEmail($var);
					}
					break;

				case 'url':
					$this->validateUrl($var);
					if(!array_key_exists($var, $this->errors)){
						$this->sanitizeUrl($var);
					}
					break;

				case 'integer':
					$this->validateNumeric($var, $opt['min'], $opt['max'], $opt['required']);
					if(!array_key_exists($var, $this->errors)){
						$this->sanitizeNumeric($var);
					}
					break;

				case 'string':
					$this->validateString($var, $opt['min'], $opt['max'], $opt['required'], $opt['regex']);
					if(!array_key_exists($var, $this->errors)){
						$this->sanitizeString($var);

						// Validate the string again to ensure we are still within boundaries of min/max after stripping html
						$this->validateString($var, $opt['min'], $opt['max'], $opt['required']);
					}
					break;

				case 'username':
				    $opt['min'] = (!empty($opt['min'])) ? $opt['min'] : self::DEFAULT_USERNAME_MIN_LENGTH;
				    $opt['max'] = (!empty($opt['max'])) ? $opt['max'] : self::DEFAULT_USERNAME_MAX_LENGTH;				    
				    $opt['regex'] = (!empty($opt['regex'])) ? $opt['regex'] : self::DEFAULT_USERNAME_REGEX;
				    
					$this->validateUsername($var, $opt['min'], $opt['max'], $opt['required'], $opt['regex']);
					if(!array_key_exists($var, $this->errors)){
						$this->sanitizeString($var);
					}
					break;

				case 'password':
				    $opt['min'] = (!empty($opt['min'])) ? $opt['min'] : self::DEFAULT_PASSWORD_MIN_LENGTH;
				    $opt['max'] = (!empty($opt['max'])) ? $opt['max'] : self::DEFAULT_PASSWORD_MAX_LENGTH;
				    
					$this->validatePassword($var, $opt['min'], $opt['max'], $opt['required'], $opt['regex']);
					if(!array_key_exists($var, $this->errors)){
						$this->sanitizePassword($var);
					}
					break;

				case 'float':
					$this->validateFloat($var, $opt['required']);
					if(!array_key_exists($var, $this->errors)){
						$this->sanitizeFloat($var);
					}
					break;

				case 'ipv4':
					$this->validateIpv4($var, $opt['required']);
					if(!array_key_exists($var, $this->errors)){
						$this->sanitizeIpv4($var);
					}
					break;

				case 'boolean':
					$this->validateBool($var, $opt['required']);
					if(!array_key_exists($var, $this->errors)){
						$this->sanitized[$var] = (bool) $this->data[$var];
					}
					break;
			}
		}
	}

	public function addValidationRules(array $aValidationRules)
	{
		foreach($aValidationRules as $varname => $aValidationRule) {
			$this->validation_rules[$varname] = $aValidationRule;
		}
	}

	// This function is for chaining different rules
	public function addValidationRule($varname, $type, $required=false, $min=null, $max=null, $trim=false, $regex=null){
		$this->validation_rules[$varname] = array('type'=>$type, 'required'=>$required, 'min'=>$min, 'max'=>$max, 'trim'=>$trim, 'regex'=>$regex);
		return $this;
	}

	// Merge all rules
	public function mergeValidationRules(array $rules_array){
		$this->validation_rules = array_merge($this->validation_rules, $rules_array);
	}

	private function is_set($var){
		if(!isset($this->data[$var])){
			$this->errors[$var] = ucfirst($var) . ' is not set';
		}
	}

	private function validateIpv4($var, $required=false){
		if($required==false && strlen($this->data[$var]) == 0){
			return true;
		}
		if(filter_var($this->data[$var], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === FALSE){
			$this->errors[$var] = ucfirst($var) . ' is not a valid IPv4';
		}
	}

	public function validateIpv6($var, $required=false){
		if($required==false && strlen($this->data[$var]) == 0){
			return true;
		}

		if(filter_var($this->data[$var], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === FALSE){
			$this->errors[$var] = ucfirst($var) . ' is not a valid IPv6';
		}
	}

	private function validateFloat($var, $required=false){
		if($required==false && strlen($this->data[$var]) == 0){
			return true;
		}
		if(filter_var($this->data[$var], FILTER_VALIDATE_FLOAT) === false){
			$this->errors[$var] = ucfirst($var) . ' is an invalid float';
		}
	}

	private function validateString($var, $min=0, $max=0, $required=false, $regex=null){
		if($required==false && strlen($this->data[$var]) == 0){
			return true;
		}

		if(isset($this->data[$var])){
		    if($required && strlen($this->data[$var]) == 0){
		        $this->errors[$var] = ucfirst($var) . ' is a required field';
		    }
			elseif(strlen($this->data[$var]) < $min){
				$this->errors[$var] = ucfirst($var) . ' is too short: ' . strlen($this->data[$var]) . ' characters. Minimum required is ' . $min;
			}
			elseif(strlen($this->data[$var]) > $max){
				$this->errors[$var] = ucfirst($var) . ' is too long:' . strlen($this->data[$var]) . ' characters . Max allowed is ' . $max;
			}
			elseif(!is_string($this->data[$var])){
				$this->errors[$var] = ucfirst($var) . ' is invalid';
			}
			elseif($regex && filter_var($this->data[$var], FILTER_VALIDATE_REGEXP, ['options'=>['regexp'=>$regex]]) === FALSE){
			    $this->errors[$var] = ucfirst($var) . ' failed regex test';
			}
		}
	}

	private function validateUsername($var, $min=self::DEFAULT_USERNAME_MIN_LENGTH, $max=self::DEFAULT_USERNAME_MAX_LENGTH, $required=false, $regex=self::DEFAULT_USERNAME_REGEX){
		if($required==false && strlen($this->data[$var]) == 0){
			return true;
		}

		if(isset($this->data[$var])){
			if($required && strlen($this->data[$var]) == 0){
				$this->errors[$var] = ucfirst($var) . ' is a required field';
			}
			elseif(strlen($this->data[$var]) < $min){
				$this->errors[$var] = ucfirst($var) . ' is too short';
			}
			elseif(strlen($this->data[$var]) > $max){
				$this->errors[$var] = ucfirst($var) . ' is too long';
			}
			elseif($regex && filter_var($this->data[$var], FILTER_VALIDATE_REGEXP, ['options'=>['regexp'=>$regex]]) === FALSE){
			    $this->errors[$var] = ucfirst($var) . ' failed regex test';
			}
		}
	}

	private function validatePassword($var, $min=self::DEFAULT_PASSWORD_MIN_LENGTH, $max=self::DEFAULT_PASSWORD_MAX_LENGTH, $required=false, $regex = null){
		if($required==false && strlen($this->data[$var]) == 0){
			return true;
		}

		if(isset($this->data[$var])){
			if($required && strlen($this->data[$var]) == 0){
				$this->errors[$var] = ucfirst($var) . ' is a required field';
			}
			elseif(strlen($this->data[$var]) < $min){
				$this->errors[$var] = ucfirst($var) . ' is too short';
			}
			elseif(strlen($this->data[$var]) > $max){
				$this->errors[$var] = ucfirst($var) . ' is too long.';
			}
			elseif($regex && filter_var($this->data[$var], FILTER_VALIDATE_REGEXP, ['options'=>['regexp'=>$regex]]) === FALSE){
			    $this->errors[$var] = ucfirst($var) . ' failed regex test';
			}
		}
	}

	private function validateNumeric($var, $min=0, $max=0, $required=false){
		if($required==false && strlen($this->data[$var]) == 0){
			return true;
		}
		if(filter_var($this->data[$var], FILTER_VALIDATE_INT, array("options" => array("min_range"=>$min, "max_range"=>$max)))===FALSE){
			$this->errors[$var] = ucfirst($var) . ' is an invalid number: ' . $this->data[$var];
		}
	}

	private function validateUrl($var, $required=false){
		if($required==false && strlen($this->data[$var]) == 0){
			return true;
		}
		if(filter_var($this->data[$var], FILTER_VALIDATE_URL) === FALSE){
			$this->errors[$var] = ucfirst($var) . ' is not a valid URL';
		}
	}

	private function validateEmail($var, $required=false, $regex=null){
		if($required==false && strlen($this->data[$var]) == 0){
			return true;
		}
		if(filter_var($this->data[$var], FILTER_VALIDATE_EMAIL) === FALSE){
			$this->errors[$var] = ucfirst($var) . ' is not a valid email address';
		}
		elseif($regex && filter_var($this->data[$var], FILTER_VALIDATE_REGEXP, ['options'=>['regexp'=>$regex]]) === FALSE){
		    $this->errors[$var] = ucfirst($var) . ' failed regex test';
		}
	}

	private function validateBool($var, $required=false){
		if($required==false && strlen($this->data[$var]) == 0){
			return true;
		}

		if(filter_var($this->data[$var], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === NULL){
			$this->errors[$var] = ucfirst($var) . ' is invalid (' . $this->data[$var] . ')';
		}
	}

	########## SANITIZING METHODS ############
	public function sanitizeEmail($var){
		$email = preg_replace( '((?:\n|\r|\t|%0A|%0D|%08|%09)+)i' , '', $this->data[$var] );
		$this->sanitized[$var] = (string) filter_var($email, FILTER_SANITIZE_EMAIL);
	}

	private function sanitizeUrl($var){
		$this->sanitized[$var] = (string) filter_var($this->data[$var],  FILTER_SANITIZE_URL);
	}

	private function sanitizeNumeric($var){
		$this->sanitized[$var] = (int) filter_var($this->data[$var], FILTER_SANITIZE_NUMBER_INT);
	}

	private function sanitizeString($var){
		$this->sanitized[$var] = (string) filter_var($this->data[$var], FILTER_SANITIZE_STRING);
	}

	private function sanitizePassword($var){
		$this->sanitized[$var] = (string) filter_var($this->data[$var], FILTER_SANITIZE_STRING);
	}

	private function sanitizeMagicQuotes($var){
		$this->sanitized[$var] = (string) filter_var($this->data[$var], FILTER_SANITIZE_MAGIC_QUOTES);
	}

	private function sanitizeFloat($var){
		$this->sanitized[$var] = (float) filter_var($this->data[$var], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
	}
}