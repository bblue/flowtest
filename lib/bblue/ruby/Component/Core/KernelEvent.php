<?php

namespace bblue\ruby\Component\Core;

use bblue\ruby\Component\EventDispatcher\EventInterface;

interface KernelEvent extends EventInterface
{
	const BOOTED = 'kernel.booted';
	const PACKAGE = 'kernel.package';
	const REQUEST = 'kernel.request';
	const RESPONSE = 'kernel.response';
	const DISPATCHER = 'kernel.dispatcher';
	const ROUTER = 'kernel.router';
	const SESSION = 'kernel.session';
}