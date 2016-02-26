<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 22:07
 */

namespace bblue\ruby\Component\Router;

use bblue\ruby\Component\Core\iRequest;

interface iRouteFactory
{
    public function buildFromRequest(iRequest $request): iRoute;
}