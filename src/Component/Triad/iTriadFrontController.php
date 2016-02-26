<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 05.02.2016
 * Time: 23:09
 */

namespace bblue\ruby\Component\Triad;

use bblue\ruby\Component\Request\iInternalRequest;
use bblue\ruby\Component\Response\iResponse;

interface iTriadFrontController
{
    public function handle(iInternalRequest $request): iResponse;
}