<?php

namespace bblue\ruby\Component\Module;

use bblue\ruby\Component\HttpFoundation\Response;
use bblue\ruby\Component\Router\Route;
use bblue\ruby\Component\Container\ContainerAwareInterface;
use bblue\ruby\Component\Container\ContainerAwareTrait;
use bblue\ruby\Component\Core\AbstractRequest;

abstract class AbstractView implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    
    protected $response;
    protected $request;
    
	public function __construct(Response $response, AbstractRequest $request)
	{
		$this->response = $response;
		$this->request = $request;
	}
}