<?php

namespace bblue\ruby\Component\Router;

class Route
{
    /**
    * @var array $aRedirectRouteLog[] Route Array that holds reference to other past route objects
    */
    private $aRedirectRouteLog = array();
    private $url;
    
    public function __construct($url, array $aRouteParameters)
    {
    	$this->_params = $aRouteParameters;
    	$this->url = $url;
    }
    
    public function redirectedFrom(Route $route)
    {
        $this->aRedirectRouteLog[] = $route;
    }
    
    public function getFallback()
    {
        return $this->hasFallback() ? : false;
    }
    
    public function getUrl()
    {
    	return $this->url;
    }
    
    public function hasFallback()
    {
        return true; //@todo: Skriver fereid denne. Returns fallback if it has one
    }
    
    public function getControllerName()
    {
    	return (isset($this->_params['CONTROLLER'])) ? $this->_params['CONTROLLER'] : null;
    }
    
    public function getControllerAction()
    {
    	return (isset($this->_params['ACTION'])) ? $this->_params['ACTION'] : null;
    }
    
    public function getViewName()
    {
    	return (isset($this->_params['VIEW'])) ? $this->_params['VIEW'] : null;
    }
    
    public function option($key)
    {
        return (isset($this->_params[$key])) ? $this->_params[$key] : null;
    }
}