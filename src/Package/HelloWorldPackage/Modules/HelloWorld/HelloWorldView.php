<?php

namespace bblue\ruby\Package\HelloWorldPackage\Modules\HelloWorld;

use bblue\ruby\Component\Form\Form;
use bblue\ruby\Package\TwigPackage\AbstractTwigAwareView;

class HelloWorldView extends AbstractTwigAwareView
{
	const MODULE_NAME = 'HelloWorld';
	
	public function test()
	{
	    $form = new Form('testform');
	    $form->createElement('input', 'input', 'input');
	    

	    
		$tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/index.twig');
		$this->response->setOutput($tpl->render(['name' => 'Aleksander', 'age' => 29, 'form'=>$form]));
	}
	
	public function fileUpload()
	{
	    $aUploadHandlerResonse = $this->response->getResponseData();
	    $this->response->setOutput(json_encode($aUploadHandlerResonse));
	}

	public function addUser()
	{
		$this->response->setOutput('success');
	}
}