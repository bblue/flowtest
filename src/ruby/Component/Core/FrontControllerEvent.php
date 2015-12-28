<?php

namespace bblue\ruby\Component\Core;

use bblue\ruby\Component\EventDispatcher\EventInterface;

interface FrontControllerEvent extends EventInterface
{
	const ROUTED = 'frontcontroller.routed';
	const CAUGHT_EXCEPTION = 'frontcontroller.caught_exception';
}