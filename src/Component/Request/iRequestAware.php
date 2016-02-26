<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 09.02.2016
 * Time: 17:32
 */

namespace bblue\ruby\Component\Request;

interface iRequestAware
{
    public function setRequest(iInternalRequest $request);
}