<?php

namespace bblue\ruby\Package\FirewallPackage;

use bblue\ruby\Component\Package\AbstractPackage;
use bblue\ruby\Component\EventDispatcher\Event;
use bblue\ruby\Component\Router\Router;
use bblue\ruby\Component\Router\RouterEvent;
use bblue\ruby\Component\Router\Route;
use bblue\ruby\Entities\User;

/**
 * @todo En brannmur blokkerer, den router ikke. Disse route elemenene slik som guest login hører vel derfor ikke egentlig hjemme i en brannmur, men i recognition service
 *
 */
final class FirewallPackage extends AbstractPackage
{
	public function boot()
	{
	    $this->interceptAnonomyousUser();
	}
	
	/**
	 * Method to intercept the router and check if user is logged in
	 */
	private function interceptAnonomyousUser()
	{
	    // Register listener to intercept the router
	    $this->eventDispatcher->addListener(RouterEvent::ROUTE, function(Event $event) {
	        $user = $this->container->get('auth', true)->getUser();
	        $router = $event->router;
	        $route = $router->route;
	    
	        if($this->_redirectToLoginPage($route, $user)) {
	            $this->logger->notice('Firewall intercepted route: {User not logged in}');
	            $router->redirect($route)->to(Router::LOGIN_URL);
	        }
	    });	    
	}
	
	## Firewall rules ## @todo flytte til egne klasser
	
	private function _redirectToLoginPage(Route $route, User $user)
	{
		if($route->getUrl() == Router::LOGIN_URL) {
			return false;
		}

		if($user->isGuest() === false) {
			return false;
		}

		if($this->config->fw_forcedLogin) {
			return !$route->option('CAN_BYPASS_FORCED_LOGIN');
		}
	}
}
