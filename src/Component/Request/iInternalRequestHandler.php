<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 18.02.2016
 * Time: 23:00
 */

namespace bblue\ruby\Component\Request;

use bblue\ruby\Component\Response\iResponse;

interface iInternalRequestHandler
{
    public function handle(iInternalRequest $request): iResponse;
}