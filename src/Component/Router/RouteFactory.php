<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 22:10
 */

namespace bblue\ruby\Component\Router;


use bblue\ruby\Component\Core\iRequest;

class RouteFactory implements iRouteFactory
{
    public function buildFromRequest(iRequest $request): iRoute
    {
        // TODO: Implement buildFromRequest() method.
    }
}