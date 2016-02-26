<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 08.02.2016
 * Time: 17:23
 */

namespace bblue\ruby\Component\Request;


trait RequestHandlerAwareTrait
{
    /**
     * @var RequestHandler
     */
    protected $requestHandler;

    public function setRequestHandler(RequestHandler $requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }
}