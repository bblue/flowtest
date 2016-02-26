<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 17:15
 */

namespace bblue\ruby\Component\Triad;

use bblue\ruby\Component\Request\iInternalRequest;

interface iRubyController
{
    /**
     * Inject a request object into the controller. This is required by all ruby controllers
     * @param iInternalRequest $request
     * @return mixed
     */
    public function setRequest(iInternalRequest $request);
}