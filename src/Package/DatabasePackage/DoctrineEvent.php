<?php

namespace bblue\ruby\Package\DatabasePackage;

use bblue\ruby\Component\EventDispatcher\EventInterface;

interface DoctrineEvent extends EventInterface
{
	const SCHEDULE_FLUSH   = 'package.doctrine.schedule_flush';
	const FLUSHED = 'package.doctrine.flushed';
}