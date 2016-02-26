<?php
/**
 * Created by PhpStorm.
 * User: Aleksander Lanes
 * Date: 21.02.2016
 * Time: 14:09
 */

namespace bblue\ruby\Component\Request;

use bblue\ruby\Component\HttpFoundation\Response;

final class FallbackResponse extends Response
{
    /**
     * FallbackResponse constructor.
     * @param ErrorHandlerException $e
     */
    public function __construct(ErrorHandlerException $e)
    {
        $this->setOutput($this->makeOutput($e));
    }

    private function makeOutput(ErrorHandlerException $e)
    {
        $request = $e->getRequest();
        $this->setStatusCode(500);
        switch($request->getRequestType()) {
            case aRequest::$CLI_REQUEST_TYPE:
                return 'Unable to recover from multiple errors while handling a request object';
                break;
            case aRequest::$HTTP_REQUEST_TYPE:
                return $this->buildHtmlReply($e);
                break;
        }
    }

    private function buildHtmlReply(ErrorHandlerException $e)
    {
        return <<<EOT
<!DOCTYPE html>
<html lang="en">
	<head>
	    <meta charset="utf-8">
	    <title>Ruby Error</title>
	    <meta http-equiv="X-UA-Compatible" content="IE=edge">
	    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	    <meta name="description" content="Ruby default error page">
	    <meta name="author" content="Aleksander Lanes">

	    <link rel="stylesheet" type='text/css' href="/assets/css/styles.css" />
	    <link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600' rel='stylesheet' type='text/css'>
	</head>
	<body>
		<div class="container">
			<div class="jumbotron" style="text-align: center;">
				<h1>Critical Website Error!</h1>
				<p class="lead">{$e->getRequest()->getError()->getMessage()}</p>
				<p>
				  <a class="btn navbar-btn btn-xs btn-primary" href="/">Back to frontpage</a>
				</p>
			</div>
			<p class="lead">Exception: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}</p>
			<p>{$this->makePrettyException($e)}</p>
			<p class="lead">Exception: {$e->getPrevious()->getMessage()} in {$e->getPrevious()->getFile()}:{$e->getPrevious()->getLine()}</p>
			<p>{$this->makePrettyException($e->getPrevious())}</p>
			<p class="lead">Exception: <strong>{$e->getRequest()->getError()->getMessage()}</strong> in
			{$e->getRequest()
            ->getError()
            ->getFile()}:<mark>{$e->getRequest()->getError()->getLine()}</mark></p>
			<p>{$this->makePrettyException($e->getRequest()->getError())}</p>
		</div><!--/.container -->
	</body>
</html>
EOT;
    }

    function MakePrettyException(\Throwable $e) {
        $trace = $e->getTrace();
        $result = '';
        foreach($trace as $element) {
            if(isset($element['class'])) {
                $result .= $element['class'];
                $result .= '->';
            }
            $result .= $element['function'];
            $result .= '();<br />';
        }

        return $result;
    }
}