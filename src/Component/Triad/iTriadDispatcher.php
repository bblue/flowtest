<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 15:15
 */

namespace bblue\ruby\Component\Triad;

use bblue\ruby\Component\Request\iInternalRequest;
use bblue\ruby\Component\Response\iResponse;

interface iTriadDispatcher
{
    public function dispatch(iInternalRequest $request, iTriad $triad, string $command = null): iResponse;
    public function isRedirectResponse(iResponse $response): bool;
}