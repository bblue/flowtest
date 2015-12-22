<?php

namespace bblue\ruby\Component\Core;

use bblue\ruby\Component\EventDispatcher\EventInterface;

interface DispatcherEvent extends EventInterface
{
	const CONTROLLER_SUCCESS   = 'dispatcher.controller.success';
	const CONTROLLER_LOADED    = 'dispatcher.controller.loaded';
	const VIEW_LOADED          = 'dispatcher.view.loaded';
	const VIEW_SUCCESS         = 'dispatcher.view.success';
}