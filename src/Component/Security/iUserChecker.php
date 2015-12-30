<?php
namespace bblue\ruby\Component\Security;

interface iUserChecker
{
    /**
     * Check if the user object actually exists in any of the user provider(s)
     * @return bool
     */
    public function exists();
    
    /**
     * Check if the user has been blocked from the site
     * @return bool
     */
    public function blocked();
    
    /**
     * Check if the user is on lockdown
     * @return bool
     */
    public function locked();
    
    /**
     * Check if the user is active
     * @return bool
     */
    public function active();
}