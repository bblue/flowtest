<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 21:09
 */

namespace bblue\ruby\Component\Triad;

use bblue\ruby\Component\Core\DispatcherEvent;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
use bblue\ruby\Component\HttpFoundation\RedirectResponse;
use bblue\ruby\Component\Request\iInternalRequest;
use bblue\ruby\Component\Response\iResponse;

final class TriadDispatcher implements iTriadDispatcher, EventDispatcherAwareInterface
{
    use EventDispatcherAwareTrait;

    /**
     * Dispatches a triad and executes the route command on it
     * @param iInternalRequest $request
     * @param iTriad           $triad
     * @param string           $command
     * @return iResponse
     * @throws \Exception
     */
    public function dispatch(iInternalRequest $request, iTriad $triad, string $command = null): iResponse
    {
        $command = $command ?? 'index';
        $controllerResponse = $this->dispatchController($request, $triad, $command);
        return $this->dispatchView($request, $triad, $command, $controllerResponse);
    }

    /**
     * Internal method to dispatch to the controller
     * @param iInternalRequest $request
     * @param iTriad           $triad
     * @param string           $command
     * @return iResponse
     * @throws \Exception In case the command is not found on the controller
     */
    private function dispatchController(iInternalRequest $request, iTriad $triad, string $command)
    {
        $controller = $triad->getController();
        // Ensure the command exists
        if(!method_exists($controller, $command)) {
            throw new \Exception('Controller command does not exists');
        }
        return $controller->$command($request, $triad->getModel());
    }

    /**
     * Internal method to dispatch to the view
     * @param iInternalRequest $request
     * @param iTriad           $triad
     * @param string           $command
     * @param                  $controllerResponse
     * @return iResponse
     * @throws \Exception In case the command is not found on the view
     */
    private function dispatchView(iInternalRequest $request, iTriad $triad, string $command, $controllerResponse): iResponse
    {
        if($this->isRedirectResponse($controllerResponse)) {
            return $controllerResponse;
        }
        $view = $triad->getView();
        // Ensure the command exists
        if(!method_exists($view, $command)) {
            throw new \Exception('Controller command does not exists');
        }
        $this->eventDispatcher->dispatch(DispatcherEvent::VIEW_LOADED, ['view' => $view]);
        $response = $view->$command($controllerResponse, $request, $triad->getModel());
        if(!$response instanceof iResponse) {
            throw new \Exception('View ('.get_class($view).')->'.$command.'() must returned a response object');
        }
        return $response;
    }

    /**
     * Check if the response is a redirect request
     * @param iResponse $response
     * @return bool
     */
    public function isRedirectResponse(iResponse $response): bool
    {
        return $response instanceof RedirectResponse;
    }
}