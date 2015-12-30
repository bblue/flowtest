<?php

namespace bblue\ruby\Component\Security;

class PasswordHelper
{
    /**
     * The default password hash complexity, or the 'cost'
     * @var Integer
     */
    const DEFAULT_PASSWORD_HASH_COMPLEXITY = 12;
    
    /**
     * The default password length when generating new passwords
     * @var Integer
     */
    const DEFAULT_PASSWORD_LENGTH = 8;
    
    /**
     * The default password algorithm to use
     * @var int
     */
    const DEFAULT_PASSWORD_ALGORITHM = PASSWORD_DEFAULT;

    /**
     * Support method for the password generation
     * 
     * @param number $nbBytes
     * @throws \Exception
     * @return string
     */
    private static function _getRandomBytes($nbBytes = 32)
    {
        $bytes = openssl_random_pseudo_bytes($nbBytes, $strong);
        if (false !== $bytes && true === $strong) {
            return $bytes;
        }
        else {
            throw new \Exception("Unable to generate secure token from OpenSSL.");
        }
    }

    /**
     * Generate a new password with upper/lower/number
     *
     * @param integer $length Defaults to value of 'self::DEFAULT_PASSWORD_LENGTH'
     * @link https://gist.github.com/zyphlar/7217f566fc83a9633959
     * @return string
     */
    public static function generatePassword($length = self::DEFAULT_PASSWORD_LENGTH){
        return substr(preg_replace("/[^a-zA-Z0-9]/", "", base64_encode(self::_getRandomBytes($length+1))),0,$length);
    }  
      
    /**
     * Verify that a password matches a hash
     * 
     * @param string $password
     * @param string $hash The hash to check the password towards
     * @return boolean True if the passwords match, false otherwise
     */
    public static function matchPasswords($password, $hash)
    {
        return (password_verify($password, $hash));
    }
    
    /**
     * Check if a password requires a rehash as per provided parameters 
     * 
     * @param string $password The password to check
     * @param int $iComplexity The 'cost' or complexity of the hash
     * @param int $iAlgorithm The algorithm to use, indicated by an integer key
     * @return string|false Returns the rehashed password if required, false if a rehash is not required
     */
    public static function requiresRehash($password, $iComplexity = self::DEFAULT_PASSWORD_HASH_COMPLEXITY, $iAlgorithm = self::DEFAULT_PASSWORD_ALGORITHM)
    {
        if (password_needs_rehash($password, $iAlgorithm, array('cost' => $iComplexity))) {
            return $this->hashPassword($password, $iComplexity, $iAlgorithm);
        } else {
            return false;
        }
    }
    
    /**
     * Hash a password
     * 
     * @param string $password
     * @param int $iComplexity
     * @param int $iAlgorithm
     * @return string The hashed password
     */
    public static function hashPassword($password, $iComplexity = self::DEFAULT_PASSWORD_HASH_COMPLEXITY, $iAlgorithm = self::DEFAULT_PASSWORD_ALGORITHM)
    {
        return password_hash($password, $iAlgorithm, array('cost' => $iComplexity));
    }
    
    /**
     * Calculates the optimal hash complexity as per computer hardware
     * 
     * @param real $fTimeTarget
     * @param number $iInitialComplexity
     * @param unknown $iAlgorithm
     * @return number
     */
    public static function getAppropriateHashCost($fTimeTarget = 0.2, $iInitialComplexity = 9, $iAlgorithm = self::DEFAULT_PASSWORD_ALGORITHM)
    {
        $cost = $iInitialComplexity;
        do {
            $cost++;
            $start = microtime(true);
            $this->hashPassword('ab.cd.ef', $cost, $iAlgorithm);
            $end = microtime(true);
        } while (($end - $start) < $fTimeTarget);
    
        return $cost;
    }
}