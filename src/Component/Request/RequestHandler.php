<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 08.02.2016
 * Time: 16:27
 */

namespace bblue\ruby\Component\Request;


use bblue\ruby\Component\Container\Container;
use bblue\ruby\Component\Core\iAdapterAware;
use bblue\ruby\Component\Core\iAdapterImplementation;
use bblue\ruby\Component\Response\iResponse;

final class RequestHandler implements iRequestHandler, iAdapterAware
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var iRequestHandler[]
     */
    private $handlers = [];

    /**
     * @var iRequestFactory
     */
    private $factory;

    /**
     * RequestHandler constructor.
     * @param Container       $container
     * @param iRequestFactory $factory
     */
    public function __construct(Container $container, iRequestFactory $factory)
    {
        $this->container = $container;
        $this->factory = $factory;
    }

    private function forwardToFallbackHandler(ErrorHandlerException $e): iResponse
    {
        return new FallbackResponse($e);
    }

    /**
     * @return iRequestFactory
     */
    public function getFactory(): iRequestFactory
    {
        return $this->factory;
    }

    public function handle(iRequest $request): iResponse
    {
        try {
            return $this->forwardToHandler($request);
        } catch (ErrorHandlerException $e) {
            /** @var iInternalErrorRequest $request */
            return $this->forwardToFallbackHandler($e);
        } catch (\Throwable $t) {
            return $this->forwardToErrorHandler($t, $request);
        }
    }

    public function registerAdapter(iAdapterImplementation $adapter, string $identifier = null)
    {
        /** @var iRequestHandler $adapter */
        $this->addHandler($adapter);
    }

    public function canHandle(iRequest $request): bool
    {
        return ($request instanceof iInternalErrorRequest);
    }

    private function addHandler(iRequestHandler $handler)
    {
        $this->handlers[] = $handler;
    }

    private function forwardToHandler(iRequest $request): iResponse
    {
        try {
            return $this->findHandlerForRequest($request)->handle($request);
        } catch (\Throwable $t) {
            return $this->forwardToErrorHandler($t, $request);
        }
    }

    private function forwardToErrorHandler(\Throwable $t, iRequest $request): iResponse
    {
        $errorRequest = $this->convertToErrorRequest($t, $request);
        try {
            return $this->findHandlerForRequest($request)->handle($errorRequest);
        } catch (\Throwable $t) {
            throw new ErrorHandlerException('Unable to handle error request', 0, $t,
                $errorRequest);
        }
    }

    private function convertToErrorRequest(\Throwable $t, iRequest $request)
    {
        return $this->getFactory()->buildInternalErrorRequest($t, $request);
    }

    private function findHandlerForRequest(iRequest $request): iRequestHandler
    {
        foreach($this->handlers as $handler) {
            if($handler->canHandle($request)) {
                return $handler;
            }
        }
        throw new RequestHandlerNotFoundException('No handler found for $request object');
    }
}