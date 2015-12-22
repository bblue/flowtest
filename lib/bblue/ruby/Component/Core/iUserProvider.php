<?php

namespace bblue\ruby\Component\Core;

use bblue\ruby\Entities\User;

interface iUserProvider
{
    /**
     * Retrieve a user object by user id
     * @param int $userId
     * @return User|bool Returns user on success, false otherwise
     */
    public function getById($userId);
    
    /**
     * Retrieve a user object by username
     * @param string $username
     * @return User|bool Returns user on success, false otherwise
     */
    public function getByUsername($username);
}