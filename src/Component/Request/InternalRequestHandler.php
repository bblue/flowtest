<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 18.02.2016
 * Time: 22:53
 */

namespace bblue\ruby\Component\Request;

use bblue\ruby\Component\Container\Container;
use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Container\ContainerAwareTrait;
use bblue\ruby\Component\Core\iAdapterImplementation;
use bblue\ruby\Component\Response\iResponse;
use bblue\ruby\Component\Triad\TriadFrontController;

final class InternalRequestHandler implements ContainerAwareInterface, iRequestHandler, iAdapterImplementation
{
    use ContainerAwareTrait;

    public function __construct(Container $container)
    {
        $this->setContainer($container);
    }

    public function handle(iRequest $request): iResponse
    {
        /** @var iInternalRequest $request */
        return $this->getFrontController()->handle($request);
    }

    private function getFrontController()
    {
        return new TriadFrontController(
            $this->container,
            $this->container->get('router'),
            $this->container->get('eventDispatcher'),
            $this->container->get('logger'));
    }

    public function canHandle(iRequest $request): bool
    {
        return ($request instanceof iInternalRequest);
    }
}