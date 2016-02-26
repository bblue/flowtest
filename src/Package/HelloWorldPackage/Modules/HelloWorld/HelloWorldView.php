<?php

namespace bblue\ruby\Package\HelloWorldPackage\Modules\HelloWorld;

use bblue\ruby\Component\Form\Form;
use bblue\ruby\Component\Response\iResponse;
use bblue\ruby\Package\TwigPackage\AbstractTwigAwareView;

class HelloWorldView extends AbstractTwigAwareView
{
	const MODULE_NAME = 'HelloWorld';

	public function commandHelloWorld()
    {
        
    }

	public function test(iResponse $response)
	{
	    $form = new Form('testform');
	    $form->createElement('input', 'input', 'input');

		$tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/jQueryFileUpload.twig');
		$response->setOutput($tpl->render(['name' => 'Aleksander', 'age' => 29, 'form'=>$form]));
		return $response;
	}
	
	public function fileUpload(iResponse $response)
	{
	    $aUploadHandlerResponse = $response->getResponseData();
	    $response->setOutput(json_encode($aUploadHandlerResponse));
		return $response;
	}

	public function addUser(iResponse $response)
	{
		$response->setOutput('success');
		return $response;
	}
}