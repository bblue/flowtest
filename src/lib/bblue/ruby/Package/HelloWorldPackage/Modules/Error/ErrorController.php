<?php

namespace bblue\ruby\Package\HelloWorldPackage\Modules\Error;

use bblue\ruby\Component\Module\AbstractController;

class ErrorController extends AbstractController
{
	public function do403() { return $this->getResponseObject(); }
	public function do404() { return $this->getResponseObject(); }
	public function do500() { return $this->getResponseObject(); }
}