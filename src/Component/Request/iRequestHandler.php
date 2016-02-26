<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 18.02.2016
 * Time: 22:58
 */

namespace bblue\ruby\Component\Request;


use bblue\ruby\Component\Response\iResponse;

interface iRequestHandler
{
    public function handle(iRequest $request): iResponse;
    public function canHandle(iRequest $request): bool;
}