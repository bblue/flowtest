<?php
namespace bblue\ruby\Component\Core;

use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Container\ContainerAwareTrait;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
use bblue\ruby\Component\HttpFoundation\RedirectResponse;
use bblue\ruby\Component\HttpFoundation\Response;
use bblue\ruby\Component\Logger\tLoggerAware;
use bblue\ruby\Component\Router\Route;
use Psr\Log\LoggerAwareInterface;

final class ModuleDispatcher implements ContainerAwareInterface, EventDispatcherAwareInterface, LoggerAwareInterface
{
	use ContainerAwareTrait;
	use EventDispatcherAwareTrait;
	use tLoggerAware;

	/**
	 * @todo Denne m� det ryddes i, mye!
	 * @param Route $route
	 * @throws \RuntimeException
	 * @return Ambigous <\bblue\ruby\Component\HttpFoundation\Response, \bblue\ruby\Component\HttpFoundation\RedirectResponse>
	 */
	public function dispatch(Route $route)
	{
	    // Get the controller
		if(!$controller = $this->container->get($route->getControllerName())) {
		    throw new \RuntimeException('Controller for route does not exist');
		}
		$this->eventDispatcher->dispatch(DispatcherEvent::CONTROLLER_LOADED, ['controller' => $controller]);

		$this->logger->info('Dispatching to "' . $route->getUrl() . '/"');
		
		// Execute command on controller
		$sAction = $route->getControllerAction();
		$response = $controller->$sAction();
		
		if($response instanceof Response) {
		    $this->eventDispatcher->dispatch(DispatcherEvent::CONTROLLER_SUCCESS, ['response'=>$response]);
			$this->container->register($response, 'response');
		} else {
			throw new \RuntimeException('Controller must return a response object');
		}
		
		if($response instanceof RedirectResponse) {
		    $this->logger->info('Redirecting to ' . $response->getUrl());
		    // @todo: Set flash message... 
			return $response;
		}
		
		// Get the view
		$view = $this->container->get($route->getViewName());
		$this->eventDispatcher->dispatch(DispatcherEvent::VIEW_LOADED, ['view' => $view]);

		// Do action on view
		$viewResponse = $view->$sAction();

		if($viewResponse instanceof Response || $viewResponse === null) {
		    if($viewResponse !== $response && !is_null($viewResponse)) {
		        $response = $viewResponse;
		    }
		    
		    $this->eventDispatcher->dispatch(DispatcherEvent::VIEW_SUCCESS, ['response'=>$response]);
			$this->container->register($response, 'response');
		} else {
		    throw new \RuntimeException('View must return a response object');
		}
		
		if($response instanceof RedirectResponse) {
		    $this->logger->info('Redirecting to ' . $response->getUrl());
		    //@todo: set flash message
		}
		
		// Return the response object
		return $response;			
	}
}