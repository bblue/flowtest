<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 15:18
 */

namespace bblue\ruby\Component\Triad;

use bblue\ruby\Component\Core\iRequest;
use bblue\ruby\Component\Core\iResponse;

interface iTriadController
{
    public function execute(iTriad $triad, iRequest $request = null): iResponse;

    public function setRequest(iRequest $request);
}