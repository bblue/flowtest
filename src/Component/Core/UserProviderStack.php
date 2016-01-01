<?php

namespace bblue\ruby\Component\Core;

final class UserProviderStack implements iUserProviderStack
{
    private $_stack = array();
    
    public function add(iUserProvider $provider, $prepend = false)
    {
        return ($prepend) ? array_unshift($this->_stack, $provider) : array_push($this->_stack, $provider);
    }
    
    public function getById($userId)
    {
        if($this->hasProvider()) {
            foreach($this->_stack as $provider) {
                if($user = $provider->getById($userId)) {
                    return $user;
                }
            }
            throw new \Exception('No user provideres were able to return a user with this ID ('. $userId . ')');
        }
        throw new \Exception('No user provider in stack');
    }
    
    public function getByUsername($username)
    {
        if($this->hasProvider()) {
            foreach($this->_stack as $provider) {
                if($user = $provider->getByUsername($username)) {
                    return $user;
                }
            }
        } else {
            throw new \Exception('No user provider in stack');
        }
    }
    
    public function hasProvider()
    {
        return !empty($this->_stack);
    }
}