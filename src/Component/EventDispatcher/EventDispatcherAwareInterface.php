<?php

namespace bblue\ruby\Component\EventDispatcher;

interface EventDispatcherAwareInterface
{
	public function setEventDispatcher(EventDispatcher $eventDispatcher);

	public function hasEventDispatcher();
}