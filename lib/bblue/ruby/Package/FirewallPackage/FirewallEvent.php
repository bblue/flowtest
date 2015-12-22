<?php

namespace bblue\ruby\Package\FirewallPackage;

use bblue\ruby\Component\EventDispatcher\EventInterface;

interface FirewallEvent extends EventInterface
{
	const USER_NOT_LOGGED_IN = 'package.firewall.user.notLoggedIn';
}