<?php
namespace bblue\ruby\Component\Security;

use bblue\ruby\Entities\User;

final class AuthToken implements iAuthToken
{
    private $valid = null;
    
    private $user;
    
    private $_data = array(
        'userId'            => null,
        'userAgent'         => null,
        'clientAddress'     => null,
        'loginHash'         => null,
    );
    
    public function __construct($data = null)
    {   
        if($data) {
            $data = array_merge($this->_data, $data);
            foreach($data as $key => $value) {
                $this->_data[$key] = $value;
            }
        }
    }
    
    public function isValid($valid = null)
    {
        if(isset($valid) && is_bool($valid)) {
            $this->valid = $valid;
        }
        return $this->valid;        
    }
    
    public function hasUser()
    {
        return isset($this->user);
    }

    public function getUser()
    {
        if(!$this->hasUser()) {
            throw new Exception('No user set in AuthToken');
        }
        return $this->user; 
    }
    
    public function setUser(User $user)
    {
        $this->user = $user;
        $this->setUserId($user->getId());
        return $this;
    }
    
    public function setUserId($id)
    {
        $this->_data['userId'] = $id;
    }
    
    public function getUserId()
    {
        return $this->_data['userId'];
    }
    
    public function getClientAddress()
    {
        return $this->_data['clientAddress'];
    }
    
    public function setClientAddress($value)
    {
        $this->_data['clientAddress'] = $value;
    }
    
    public function getUserAgent()
    {
        return $this->_data['userAgent'];
    }
    
    public function setUserAgent($value)
    {
        $this->_data['userAgent'] = $value;
    }
    
    public function getLoginHash()
    {
        return $this->_data['loginHash'];
    }
    
    public function setLoginHash($value)
    {
        $this->_data['loginHash'] = $value;
    }
    
    public function toArray()
    {
        return $this->_data;
    }
}