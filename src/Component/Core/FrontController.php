<?php

namespace bblue\ruby\Component\Core;

use bblue\ruby\Component\Container\Container;
use bblue\ruby\Component\EventDispatcher\EventDispatcher;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
use bblue\ruby\Component\Flasher\Flasher;
use bblue\ruby\Component\Logger\tLoggerAware;
use bblue\ruby\Component\Module\AbstractController;
use bblue\ruby\Component\Router\Route;
use bblue\ruby\Component\Router\Router;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

final class FrontController extends AbstractController implements EventDispatcherAwareInterface, LoggerAwareInterface, FrontControllerEvent //@todo: Vurdere � fjerne frontcontrollerevent fullstendig
{
	use EventDispatcherAwareTrait;
	use tLoggerAware;
	
	private $dispatcher;
	private $router;
	
	/**
	 * @var Flasher
	 */
	private $flash;
	
	public function __construct(ModuleDispatcher $dispatcher, Router $router, EventDispatcher $eventDispatcher, LoggerInterface $logger, Flasher $flash, Container $container)
	{
		$this->dispatcher = $dispatcher;
		$this->router = $router;
		$this->setEventDispatcher($eventDispatcher);
		$this->setLogger($logger);
		$this->flash = $flash;
		$this->setContainer($container);
	}

	public function handle(AbstractRequest $request)
	{
	    //@todo Ikke relatert til denne klassen, men jeg m� lage et containable interface for hva som kan lagres i container
	    return $this->doDispatch($this->getRoute($request));        
	}
	
	private function doDispatch(Route $route)
	{
		try {
			return $this->dispatcher->dispatch($route);			
		} catch (Exception $e) { // if we end up here, the error could not be handled by the controller
		    $this->eventDispatcher->dispatch(FrontControllerEvent::CAUGHT_EXCEPTION, ['Exception'=>$e]);
		    
			$this->logger->critical('Unexpected exception caught by frontController ('.$e . ')');
			
		    // Attempt to show server 500 error
		    if($route->getUrl() == Router::SERVER_500_ERROR_URL) {
		        $this->logger->alert('Error occured during render of 500 page! Unable to show error page to visitor.');
		        throw $e;
		    }
		    
	        $route = $this->router
        		        ->redirect($route)
        		        ->to(Router::SERVER_500_ERROR_URL);

		    try {
		        return $this->dispatcher->dispatch($route);  
		    } catch (Exception $e) {
		        // If everything failes-> route to static error page
		        //@todo gj�re denne smartere/bedre
		        throw new Exception('The site encountered double exceptions. Unable to recover.', 0, $e);
		    } finally {
		        $this->logger->emergency('Error occured during route to 500 error! Unable to recover from this exception.');
		    }
		}
	}
	
	private function getRoute(AbstractRequest $request)
	{
	    try {
	        return $this->router->route($request);    
	    } catch (Exception $e) {
	        throw new RoutingException($e->getMessage());
	    }	
	}
}