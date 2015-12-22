<?php

namespace bblue\ruby\Component\Module;

use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Container\ContainerAwareTrait;
use bblue\ruby\Component\Core\AbstractRequest;
use bblue\ruby\Component\HttpFoundation\Response;

abstract class AbstractController implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
	protected $request;
	
	public function __construct(AbstractRequest $request)
	{
		$this->setRequest($request);
	}
	
	public function setRequest(AbstractRequest $request)
	{
		$this->request = $request;
	}
	
	/** @todo: Skrive ferdig denne */
	protected function getResponseObject($param = null)
	{
	    // Check request for type of request and return the correct response object
	    return new Response($param);
	}
}