<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 16:56
 */

namespace bblue\ruby\Component\Triad;

use bblue\ruby\Component\Router\iRoute;

interface iTriadFactory
{
    public function build(iRoute $route): iTriad;
}