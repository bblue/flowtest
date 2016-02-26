<?php

namespace bblue\ruby\Component\Router;

final class Route implements iRoute
{
    /**
    * @var array $aRedirectRouteLog[] Route Array that holds reference to other past route objects
    */
    private $aRedirectRouteLog = array();
    private $url;

    private $_params;

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
    
    public function getCommand(): string
    {
    	return (isset($this->_params['ACTION'])) ? $this->_params['ACTION'] : null;
    }

    public function getControllerCN(): string
    {
        return $this->_params['CONTROLLER'] ??  null;
    }

    public function getViewCN(): string
    {
        return $this->_params['VIEW'] ??  null;
    }

    public function getModelCN(): string
    {
        return $this->_params['MODEL'] ??  null;
    }

    public function option($key)
    {
        return (isset($this->_params[$key])) ? $this->_params[$key] : null;
    }

    #################3 The new stuff ######################

    /**
     * @param mixed $command
     * @return Route
     */
    public function setCommand($command): self
    {
        $this->_params['ACTION'] = $command;
        return $this;
    }

    public function getView(): string
    {
        // TODO: Implement getView() method.
    }

    public function hasModelFqcn(): bool
    {
        return isset($this->_params['MODEL']);
    }
}