<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 20.02.2016
 * Time: 20:04
 */

namespace bblue\ruby\Component\Request;


use bblue\ruby\Component\Response\iResponse;

final class ExternalRequestHandler implements iExternalRequestHandler
{

    public function handle(iRequest $request): iResponse
    {
        /** @var iExternalRequest $request */
        return $this->handleExternalRequest($request);
    }

    public function canHandle(iRequest $request): bool
    {
        return ($request instanceof iExternalRequest);
    }

    private function handleExternalRequest(iExternalRequest $request): iResponse
    {

    }
}