<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 22:03
 */

namespace bblue\ruby\Component\Triad;


use bblue\ruby\Component\Container\Container;
use bblue\ruby\Component\Core\iRequest;
use bblue\ruby\Component\Core\iResponse;
use bblue\ruby\Component\HttpFoundation\RedirectResponse;
use bblue\ruby\Component\Router\iRoute;
use bblue\ruby\Component\Router\Router;

final class TriadController implements iTriadController
{
    /**
     * @var iTriadDispatcher
     */
    private $triadDispatcher;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Container
     */
    private $container;

    private $request;

    /**
     * TriadController constructor.
     * @param Container        $container
     * @param iTriadDispatcher $triadDispatcher
     * @param Router           $router
     */
    public function __construct(Container $container, iTriadDispatcher $triadDispatcher, Router $router)
    {
        $this->triadDispatcher = $triadDispatcher;
        $this->router = $router;
        $this->container = $container;
    }

    public function execute(iTriad $triad, iRequest $request = null): iResponse
    {
        if(isset($request)) {
            $this->setRequest($request);
        }
        $route = $this->router->route($this->request);
        return $this->handle($triad, $route, $this->request);
    }

    private function handle(iTriad $triad, iRoute $route, iRequest $request): iResponse
    {
        //@todo Check if the triad has been built or not
        $triad = $this->completeTriadBuild($triad, $route, $request);
        return $this->dispatch($triad, $route, $request);
    }


    private function dispatch(iTriad $triad, iRoute $route, iRequest $request): iResponse
    {
        $response = $this->triadDispatcher->dispatch($triad);
        if($this->triadDispatcher->isRedirectResponse($response)) {
            /** @var RedirectResponse $response */
            $route = $this->router->redirect($route)->toUrl($response->getUrl());
            $response = $this->handle($triad, $route, $request);
        }
        return $response;
    }


    /**
     * @param iRequest $request
     */
    public function setRequest(iRequest $request)
    {
        $this->request = $request;
    }
}