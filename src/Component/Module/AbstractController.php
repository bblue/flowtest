<?php

namespace bblue\ruby\Component\Module;

use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Container\ContainerAwareTrait;
use bblue\ruby\Component\HttpFoundation\Response;
use bblue\ruby\Component\Request\iRequestAware;
use bblue\ruby\Component\Request\RequestAwareTrait;
use bblue\ruby\Component\Request\RequestHandlerAwareTrait;
use bblue\ruby\Component\Triad\iRubyController;

abstract class AbstractController implements ContainerAwareInterface, iRubyController, iRequestAware
{
    use ContainerAwareTrait;
    use RequestHandlerAwareTrait;
    use RequestAwareTrait;

	/** @todo: Skrive ferdig denne */
	protected function getResponseObject($param = null)
	{
	    // Check request for type of request and return the correct response object
	    return new Response($param);
	}
}