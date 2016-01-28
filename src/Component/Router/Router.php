<?php

namespace bblue\ruby\Component\Router;

use bblue\ruby\Component\Core\AbstractRequest;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
use bblue\ruby\Component\Logger\tLoggerAware;
use Psr\Log\LoggerAwareInterface;
use URL\Normalizer;

class Router implements EventDispatcherAwareInterface, LoggerAwareInterface
{
	use EventDispatcherAwareTrait;
	use tLoggerAware;

	const SERVER_403_ERROR_URL = 'error/403';
	const SERVER_404_ERROR_URL = 'error/404';
	const SERVER_500_ERROR_URL = 'error/500';
	const LOGIN_URL = 'users/login';
	/**
	 * Array holding the route table
	 * @var array
	 */
	public $_aRouteMap = array();
	public $route;
	
	/** Constructor with dependencies injected 
	 * 
	 * @param EventDispatcher $eventDispatcher
	 * @param Logger $logger
	 * @param array $aRoutes Array with string representation of possible routes
	 */
	public function __construct($eventDispatcher, $logger, array $aRouteMap)
	{
		foreach($aRouteMap as $url => $data) {
		    $this->_aRouteMap[$this->normalizeUrl($url)] = $data; 
		}
		
		$this->setEventDispatcher($eventDispatcher);
		$this->setLogger($logger);
	}
	
	public function normalizeUrl($url)
	{
	    return (new Normalizer( $url ))->normalize();
	}
	
	public function addRoutes(array $aRoutes)
	{
	    foreach($aRoutes as $url => $data) {
	        $this->_aRouteMap[$this->normalizeUrl($url)] = $data;
	    }
	}
	
    public function redirect($mRoute)
    {
        if(is_object($mRoute)) {
            return $this->redirectRoute($mRoute);
        } elseif (is_string($mRoute)) {
            return $this->redirectUrl($mRoute);
        }
    }
	
	public function redirectRoute(Route $route)
	{
	    $this->route = $route;
	    return $this;
	}
	
	public function redirectUrl($url)
	{
	    return $this->redirectRoute($this->getRouteByUrl($url));
	}

	private function getRouteByUrl($url)
	{
		if ($this->routeByUrlExists($url)) {
			$route = $this->buildRouteObject($url, $this->_aRouteMap[$url]);
			return $route;
		} else {
			throw new RouteNotFoundException("No route handler identified for url ({$url})");
		}
	}

	public function routeByUrlExists($url)
	{
		return array_key_exists($this->normalizeUrl($url), $this->_aRouteMap);
	}

	public function buildRouteObject($url, array $aRouteParameters)
	{
		$url = $this->normalizeUrl($url);
		return new Route($url, $aRouteParameters);
	}

	/**
    * Alias of $this->to()
    */
    public function redirectTo($mRoute)
    {
        return $this->to($mRoute);
    }

	public function to($mRoute)
    {
        if(is_object($mRoute)) {
            return $this->toRoute($mRoute);
        } elseif (is_string($mRoute)) {
            return $this->toUrl($mRoute);
        }
    }

	public function toRoute(Route $route)
	{
	    if(isset($this->route)) {
	    	$this->logger->info('Redirecting from "' . (($this->route->getUrl())?:'') . '" to "' . $route->getUrl() . '"');
	        $route->redirectedFrom($this->route);
            $this->route = $route;
            return $this->route;
	    } else {
	        throw new \RuntimeException("A route to redirect from is not set");
	    }
	}

	public function toUrl($url)
	{
	    $url = $this->normalizeUrl($url);
	    return $this->toRoute($this->getRouteByUrl($url));
	}

	/**
	 * Entry method to the router. Takes a request object and checks it towards defined routes, then trigger the
	 * dispatcher for further processing by any firewall
	 * @param AbstractRequest $request
	 * @return Route
	 * @throws RouteNotFoundException
	 */
	public function route(AbstractRequest $request)
	{
		// Look for matching routes in route map
		try {
			$route = $this->getRouteByUrl($this->normalizeUrl($request->getUrl()));
		} catch (RouteNotFoundException $e) {
			$this->logger->warning($e->getMessage());
			$route = $this->getRouteByUrl($this->normalizeUrl(self::SERVER_404_ERROR_URL));
		}

		// Define current route active route by Router
		$this->route = $route;

		// Trigger listeners and detailed routes by firing off the route event.
		$this->eventDispatcher->dispatch(RouterEvent::ROUTE, ['router' => $this]);

		return $this->route;
	}
}