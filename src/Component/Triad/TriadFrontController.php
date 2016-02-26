<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 05.02.2016
 * Time: 23:11
 */

namespace bblue\ruby\Component\Triad;


use bblue\ruby\Component\Container\Container;
use bblue\ruby\Component\Request\iInternalRequest;
use bblue\ruby\Component\Response\iResponse;
use bblue\ruby\Component\Router\iRoute;
use bblue\ruby\Component\Router\Router;

final class TriadFrontController implements iTriadFrontController
{
    /**
     * @var Container
     */
    private $container;
    /**
     * @var Router
     */
    private $router;

    /**
     * @var iTriadDispatcher
     */
    private $triadDispatcher;

    /**
     * @var iTriadFactory
     */
    private $triadFactory;

    public function __construct(Container $container, Router $router)
    {
        $this->container = $container;
        $this->router = $router;
    }

    private function getTriadDispatcher()
    {
        if (!isset($this->triadDispatcher)) {
            $this->createTriadDispatcher();
        }
        return $this->triadDispatcher;
    }

    private function setTriadDispatcher(iTriadDispatcher $triadDispatcher)
    {
        $this->triadDispatcher = $triadDispatcher;
    }

    private function createTriadDispatcher(): iTriadDispatcher
    {
        $triadDispatcher = new TriadDispatcher();
        $this->setTriadDispatcher($triadDispatcher);
        $this->container->register($triadDispatcher);
        return $triadDispatcher;
    }

    private function getTriadFactory(): iTriadFactory
    {
        if (!isset($this->triadFactory)) {
            $this->setTriadFactory($this->createTriadFactory());
        }
        return $this->triadFactory;
    }

    private function setTriadFactory(iTriadFactory $triadFactory)
    {
        $this->triadFactory = $triadFactory;
    }

    private function createTriadFactory(): iTriadFactory
    {
        return new TriadFactory($this->container, $this->getTriadDispatcher());
    }

    /**
     * Handles a request object and returns the response
     * @param iInternalRequest $request
     * @return iResponse
     */
    public function handle(iInternalRequest $request): iResponse
    {
        $route = $this->router->route($request);
        //try {
            return $this->dispatch($route, $request);
        /*} catch (\Exception $e) {
            //@todo make loggable?
            $this->container->get('logger')->error($e->getMessage());
            $route = $this->router->redirect($route)->to(Router::SERVER_500_ERROR_URL);
            return $this->dispatch($route, $request);
        }
        */
    }

    private function dispatch(iRoute $route, iInternalRequest $request): iResponse
    {
        $triad = $this->getTriadFactory()->build($route);
        return $this->triadDispatcher->dispatch($request, $triad, $route->getCommand());
    }
}