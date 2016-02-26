<?php

namespace bblue\ruby\Package\HelloWorldPackage\Modules\Error;

use bblue\ruby\Component\Request\iInternalErrorRequest;
use bblue\ruby\Component\Request\iInternalRequest;
use bblue\ruby\Component\Response\iResponse;
use bblue\ruby\Package\TwigPackage\AbstractTwigAwareView;

class ErrorView extends AbstractTwigAwareView
{
	const MODULE_NAME = 'Error';

	public function do403(iResponse $response)
	{
	    $tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/full-page-error.twig');
	    $iResponseCode = 403;
	    $sResponseCodeText = $response->getResponseCodeText($iResponseCode);
	    $response->setOutput($tpl->render( [
	        'sErrorHeading'            => "Error {$iResponseCode}: {$sResponseCodeText}",
	        'sErrorDescription'        => 'You do not have the correct permissions to access this area. Please check your login and try again. If you believe this is an error, please contact the site administrator.',
	        'html_title'               => $sResponseCodeText
        ]));
		$response->setStatusCode($iResponseCode);
		return $response;
	}
	
	public function do404(iResponse $response)
	{
	    $tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/full-page-error.twig');
	    $iResponseCode = 404;
	    $sResponseCodeText = $response->getResponseCodeText($iResponseCode);
	    $response->setOutput($tpl->render( [
	        'sErrorHeading'            => "Error {$iResponseCode}: {$sResponseCodeText}",
	        'sErrorDescription'        => 'The requested resource could not be found. It has either moved, is no longer accessible, or you have typed an incorrect address.',
	        'html_title'               => $sResponseCodeText
        ]));
	    $response->setStatusCode($iResponseCode);
		return $response;
	}
	
	public function do500(iResponse $response, iInternalRequest $request)
	{

	    $tpl = $this->twig->loadTemplate('@'.self::MODULE_NAME.'/full-page-error.twig');
	    $iResponseCode = 500;
	    $sResponseCodeText = $response->getResponseCodeText($iResponseCode);

        $data = [
            'sErrorHeading'            => "Error {$iResponseCode}: {$sResponseCodeText}",
            'sErrorDescription'        => 'It appears you have encountered a server error. The issue has automatically been reported to the administrators and will be investigated.',
            'html_title'               => $sResponseCodeText,
        ];
		if($request instanceof iInternalErrorRequest) {
            /** @var iInternalErrorRequest $request */
            $data['exception']    = $request->getError();
        }
	    $response->setOutput($tpl->render($data));
	    $response->setStatusCode($iResponseCode);
		return $response;
	}
}