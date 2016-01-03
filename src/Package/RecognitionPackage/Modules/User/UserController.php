<?php

namespace bblue\ruby\Package\RecognitionPackage\Modules\User;

use bblue\ruby\Component\Module\AbstractController;
use bblue\ruby\Package\RecognitionPackage\Modules\User\Forms\LoginForm;
use bblue\ruby\Package\RecognitionPackage\NativeLogin;

class UserController extends AbstractController
{
	public function forgotPassword()
	{
	    return $this->getResponseObject();
	}
	
	public function login()
	{
	    return $this->nativeLogin();
	}
	
	public function nativeLogin()
	{
	    $form = new LoginForm('loginform', $this->request->_post());
	    if($form->isSubmitted() && $form->isValid()) {
	        $loginProvider = $this->container->get('nativeLogin'); //@TODO: denne burde kalles på en mer generell måte ala modules for HMVC
	        $loginProvider->handle($form);
	    }
	    return $this->getResponseObject(['loginForm'=>$form]);
	}
	
	public function logout()
	{
	    $auth = $this->container->get('auth');
	    $auth->logout($auth->getUser(), $this->request->getSession());//@todo auth-logout() skulle ikke trenge user object etter at vi har gjort auth til eier av user
	    return $this->getResponseObject();
	}
}
