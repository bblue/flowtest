<?php

namespace bblue\ruby\Package\RecognitionPackage\Modules\User;

use bblue\ruby\Package\TwigPackage\AbstractTwigAwareView;
use bblue\ruby\Component\HttpFoundation\HttpRequest;
use bblue\ruby\Component\HttpFoundation\RedirectResponse;
use bblue\ruby\Component\Router\Router;

class UserView extends AbstractTwigAwareView
{
	const MODULE_NAME = 'User';

	public function login()
	{
	    $user = $this->container->get('auth')->getUser();

	    if($user->isGuest()) {
	        $tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/full-page-login.twig'); //@todo: Denne templaten har en dependency for helloWorld via toString() funksjonen implementert i Twig. Dette burde jeg fikse på en måte slik at det er mer synlig.
	        $this->response->setOutput($tpl->render( [
	            'html_title'               => 'Login',
	            'loginForm'                => $this->response->getResponseData()['loginForm'],
	            'user'                     => $user,
	            'request'                  => $this->request,
	            'flash'                    => $this->container->get('flash')
	        ]));
	    } else {
	        return new RedirectResponse($this->request->getTargetUrl() ? : '/');
	    }	        
	}
	
	public function logout()
	{ 
        return new RedirectResponse($this->container->get('auth')->getUser()->isGuest() ? '/' : Router::SERVER_500_ERROR_URL);
	}
	
	public function forgotPassword()
	{
	    $tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/full-page-password-reset-request.twig');
	    $this->response->setOutput($tpl->render( [
	        'html_title'              => 'Reset password',
	        'passwordResetForm'       => $this->response->getResponseData()['passwordResetForm'],
	        'user'                     => $this->container->get('auth')->getUser(),
	        'request'                  => $this->request
	    ]));
	    
	}
}