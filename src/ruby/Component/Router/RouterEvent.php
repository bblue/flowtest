<?php

namespace bblue\ruby\Component\Router;

use bblue\ruby\Component\EventDispatcher\EventInterface;

interface RouterEvent extends EventInterface
{
	const ROUTE = 'router.route';
}