<?php

namespace bblue\ruby\Package\HelloWorldPackage\Modules\Error;

use bblue\ruby\Package\TwigPackage\AbstractTwigAwareView;

class ErrorView extends AbstractTwigAwareView
{
	const MODULE_NAME = 'Error';

	public function do403()
	{
	    $tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/full-page-error.twig');
	    $iResponseCode = 403;
	    $sResponseCodeText = $this->response->getResponseCodeText($iResponseCode);
	    $this->response->setOutput($tpl->render( [
	        'sErrorHeading'            => "Error {$iResponseCode}: {$sResponseCodeText}",
	        'sErrorDescription'        => 'You do not have the correct permissions to access this area. Please check your login and try again. If you believe this is an error, please contact the site administrator.',
	        'html_title'               => $sResponseCodeText
        ]));
	    $this->response->setStatusCode($iResponseCode);
	}
	
	public function do404()
	{
	    $tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/full-page-error.twig');
	    $iResponseCode = 404;
	    $sResponseCodeText = $this->response->getResponseCodeText($iResponseCode);
	    $this->response->setOutput($tpl->render( [
	        'sErrorHeading'            => "Error {$iResponseCode}: {$sResponseCodeText}",
	        'sErrorDescription'        => 'The requested resource could not be found. It has either moved, is no longer accessible, or you have typed an incorrect address.',
	        'html_title'               => $sResponseCodeText
        ]));
	    $this->response->setStatusCode($iResponseCode);
	}
	
	public function do500()
	{
	    $tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/full-page-error.twig');
	    $iResponseCode = 500;
	    $sResponseCodeText = $this->response->getResponseCodeText($iResponseCode);
	    $this->response->setOutput($tpl->render( [
	        'sErrorHeading'            => "Error {$iResponseCode}: {$sResponseCodeText}",
	        'sErrorDescription'        => 'It appears you have encountered a server error. The issue has automatically been reported to the administrators and will be investigated.',
	        'html_title'               => $sResponseCodeText
        ]));
	    $this->response->setStatusCode($iResponseCode);
	}
}