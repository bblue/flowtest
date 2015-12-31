<?php

namespace bblue\ruby\Package\LoggerPackage;

use bblue\ruby\Component\Package\AbstractPackage;
use Psr\Log\LoggerInterface;

final class Logger extends AbstractPackage
{
    public function boot()
    {
    	// Only register logging adapters if we actually are performing logging
    	if ($this->logger->isEnabled()) {
    	    // Enable echologger
    	    $logger = $this->getLoggerClass('echologger');
    		$this->logger->registerAdapter($logger);
    	
    		// Enable filelogger
    		$logger = $this->getLoggerClass('FileLogger');
    		$this->logger->registerAdapter($logger);
    	}
        return true;
    }
    /**
    * @todo Dette må ikke hardkodes på denne måten. Jeg må revurdere hvordan disse kalles
    */
    private function getLoggerClass($sLogger)
    {
    	switch(strtolower($sLogger)) {
    		case 'filelogger':
    			return new FileLogger($this->config->sLogPath, $this->config->sLogLevelThreshold, $this->container->get('request')->getClientAddress());
    		case 'echologger':
    		    return new EchoLogger($this->config->echologger_threshold, $this->config->echologger_cache, $this->config->echologger_html, $this->config->echologger_cli);
    		default:
    			throw new \RuntimeException($sLogger . ' is not a recognized logger');
    	}
    }
}