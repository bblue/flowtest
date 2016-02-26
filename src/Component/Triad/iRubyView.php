<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 01.02.2016
 * Time: 17:17
 */

namespace bblue\ruby\Component\Triad;

use bblue\ruby\Component\Request\iInternalRequest;

interface iRubyView
{
    /**
     * Inject a request object into the view. This is required by all ruby views
     * @param iInternalRequest $request
     * @return mixed
     */
    public function setRequest(iInternalRequest $request);
}