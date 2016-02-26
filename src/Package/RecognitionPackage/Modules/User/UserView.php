<?php

namespace bblue\ruby\Package\RecognitionPackage\Modules\User;

use bblue\ruby\Component\HttpFoundation\RedirectResponse;
use bblue\ruby\Component\Request\iInternalRequest;
use bblue\ruby\Component\Response\iResponse;
use bblue\ruby\Component\Router\Router;
use bblue\ruby\Package\TwigPackage\AbstractTwigAwareView;

class UserView extends AbstractTwigAwareView
{
	const MODULE_NAME = 'User';

	public function login(iResponse $response, iInternalRequest $request)
	{
	    $user = $this->container->get('auth')->getUser();
	    if($user->isGuest()) {
	        $tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/full-page-login.twig'); //@todo: Denne templaten har en dependency for helloWorld via toString() funksjonen implementert i Twig. Dette burde jeg fikse p� en m�te slik at det er mer synlig.
	        $response->setOutput($tpl->render( [
	            'html_title'               => 'Login',
	            'loginForm'                => $response->getResponseData()['loginForm'],
	            'user'                     => $user,
	            'request'                  => $this->request,
	            'flash'                    => $this->container->get('flash')
	        ]));
	        return $response;
	    } else {
			$redirectUrl = ($request->_server('REQUEST_URI') === $request->getAddress()) ? '/' : $request->_server
			('REQUEST_URI');
	        return new RedirectResponse($redirectUrl);
	    }
	}

	public function logout()
	{ 
        return new RedirectResponse($this->container->get('auth')->getUser()->isGuest() ? '/' : Router::SERVER_500_ERROR_URL);
	}

	public function forgotPassword(iResponse $response)
	{
	    $tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/full-page-password-reset-request.twig');
	    $response->setOutput($tpl->render( [
	        'html_title'              => 'Reset password',
	        'passwordResetForm'       => $response->getResponseData()['passwordResetForm'],
	        'user'                     => $this->container->get('auth')->getUser(),
	        'request'                  => $this->request
	    ]));
	    return $response;
	}
}