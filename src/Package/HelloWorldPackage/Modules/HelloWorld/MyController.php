<?php

namespace bblue\ruby\Package\HelloWorldPackage\Modules\HelloWorld;

use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareInterface;
use bblue\ruby\Component\EventDispatcher\EventDispatcherAwareTrait;
use bblue\ruby\Component\HttpFoundation\Request;
use bblue\ruby\Component\HttpFoundation\Response;
use bblue\ruby\Component\Module\AbstractController;
use bblue\ruby\Package\RecognitionPackage\UserService;
use blueimp\jqueryFileUpload\UploadHandler;

class MyController extends AbstractController implements EventDispatcherAwareInterface
{
	use EventDispatcherAwareTrait;
	
	public function test()
	{	    
		return new Response();
	}
	
	public function fileUpload()
	{
	    $options = array(
	       'upload_url'        => '/uploads/',
	        'upload_dir' 		=> PUBLIC_PATH . '/uploads/',
	        'script_url'       => '/fileupload',
	        'accept_file_types' => '/\.(gif|jpe?g|png)$/i',
	        'max_file_size'		=> 2*10*1024*1024, //20 MB
	        'user_dirs'			=> false,
	        'print_response'   => false
	    );
	     
	    //require(BASE_PATH . '/lib/blueimp/jquery-file-upload/UploadHandler.php');
	    
	    $upload_handler = new UploadHandler($options);
	    
	    return new Response($upload_handler->get_response());
	}

	public function addUser()
	{
		/** @var UserService $userService */
		$userService = $this->container->get('UserService');
		$parameters = [
			'username'	=> 'aleksander.lanes@gmail.com',
			'password'	=> 'admin',
		];
		$user = $userService->buildMember($parameters);
		$userService->addUser($user);

        return new Response();
	}
}