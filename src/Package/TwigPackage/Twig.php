<?php

namespace bblue\ruby\Package\TwigPackage;

use bblue\ruby\Component\Package\AbstractPackage;

final class Twig extends AbstractPackage
{	
	public function boot()
	{
	    // Don't load twig package for cli request
	    if($this->container->get('request')->isCommandLineInterface()) {
	        $this->logger->debug('Twig package not required for CLI request. Aborting package boot.');
	        return false;
	    }
	    
		require realpath(VENDOR_PATH) . '/Twig/Autoloader.php';
		\Twig_Autoloader::register();
		
		$loader = new \Twig_Loader_Chain();
		$twig = new \Twig_Environment($loader, array(
				'cache'	=> realpath(VENDOR_PATH) . '/cache/twig_compilation_cache',
				'debug' => $this->config->bDebug 
		));
		
		$this->eventDispatcher->dispatch('package.twig.loaded', ['twig' => $twig]);
		
		/*$lexer = new \Twig_Lexer($twig, array(
				'tag_block'		=> ['{', '}'],
				'tag_variable'	=> ['{{', '}}'],
		));
		
		$twig->setLexer($lexer);
		*/
		
		$this->container->set($twig, 'twig');
	}
}