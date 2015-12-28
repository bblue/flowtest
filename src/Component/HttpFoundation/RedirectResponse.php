<?php

namespace bblue\ruby\Component\HttpFoundation;

class RedirectResponse extends Response
{
    private $url;
    
	public function __construct($url, $bPermanent = false)
	{
	    $this->url = $url;
	    $this->setStatusCode($bPermanent ? 301 : 302);
	}
	
	public function send()
	{
	    if(headers_sent($filename, $linenum)) {
	        throw new \Exception('Cannot modify header information - headers already sent in '.$filename.' on line '.$linenum);
	    }
	    
	    header('Location:'.$this->url, true, $this->getStatusCode());
	}
	
	public function getUrl()
	{
	    return $this->url;
	}
	
	public function getStatusCode()
	{
	    return $this->http_response_code;
	}
}