<?php

namespace bblue\ruby\Component\Config;

interface ConfigInterface
{   
    
    /**
     * Determines if the app is running in development mode based on the environment variable
     *
     * @return boolean
     */
    public function isDevMode();
}